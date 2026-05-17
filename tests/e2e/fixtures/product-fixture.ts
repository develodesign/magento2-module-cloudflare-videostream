import { execSync } from 'node:child_process';
import { request, type APIRequestContext } from '@playwright/test';

/**
 * Minimal Magento REST fixture for the Cloudflare PDP tests.
 *
 * `ensureTestAdmin()` shells out to `bin/magento admin:user:create` to
 * provision a known-credentials admin (idempotent — re-runs of the create
 * command return non-zero when the user already exists, which we treat as a
 * success). `seedProduct()` then uses that admin's REST token to create a
 * simple product, returning the storefront path for the spec to navigate to.
 * `deleteProduct()` removes it afterwards.
 *
 * `MAGENTO_ADMIN_USERNAME` / `MAGENTO_ADMIN_PASSWORD` env vars override the
 * defaults — handy when running against an environment where bin/magento
 * isn't on the same host as Playwright.
 */

const DEFAULT_USERNAME = 'playwright_test';
const DEFAULT_PASSWORD = 'playwright_PW123!';
const DEFAULT_EMAIL = 'playwright@test.local';

export interface SeededProduct {
  sku: string;
  path: string;
  token: string;
  apiContext: APIRequestContext;
}

export interface SeedOptions {
  /**
   * URL used for REST API calls. In a Codespaces environment this MUST be
   * the local Magento URL (`http://localhost:8080`) because the forwarded
   * codespace URL goes through GitHub auth and rejects programmatic POSTs.
   * Override with `MAGENTO_API_URL` for non-codespace environments.
   */
  apiURL?: string;
  sku?: string;
}

/**
 * Make sure an admin user with known credentials exists. Runs `bin/magento
 * admin:user:create`; if the user already exists the CLI exits non-zero and
 * we swallow it. Throws only when bin/magento itself is missing.
 */
export function ensureTestAdmin(): { username: string; password: string } {
  // Honour explicit override env vars only if BOTH are present — never trust
  // a default like `admin` baked into devcontainer.json because the underlying
  // DB may have been seeded from a different source. In all other cases we
  // create our own known-credentials admin via bin/magento.
  if (process.env.PLAYWRIGHT_MAGENTO_USERNAME && process.env.PLAYWRIGHT_MAGENTO_PASSWORD) {
    return {
      username: process.env.PLAYWRIGHT_MAGENTO_USERNAME,
      password: process.env.PLAYWRIGHT_MAGENTO_PASSWORD,
    };
  }

  const username = DEFAULT_USERNAME;
  const password = DEFAULT_PASSWORD;
  const email = DEFAULT_EMAIL;

  try {
    execSync(
      `bin/magento admin:user:create ` +
        `--admin-user=${username} ` +
        `--admin-password='${password}' ` +
        `--admin-email=${email} ` +
        `--admin-firstname=Playwright ` +
        `--admin-lastname=Test`,
      { stdio: 'pipe' },
    );
  } catch {
    // Already exists, or bin/magento unavailable. The token request below
    // will fail loudly with a useful message if creds don't work.
  }
  // Clear any brute-force lockout left over from a previous failed run.
  try {
    execSync(`bin/magento admin:user:unlock ${username}`, { stdio: 'pipe' });
  } catch {
    // bin/magento not on PATH — token request will fail clearly downstream.
  }
  return { username, password };
}

async function getAdminToken(
  ctx: APIRequestContext,
  username: string,
  password: string,
): Promise<string> {
  // One retry: Magento's admin-token endpoint applies brute-force throttling
  // that can surface as a transient 401 right after a previous failed attempt.
  for (let attempt = 0; attempt < 2; attempt++) {
    const res = await ctx.post('/rest/V1/integration/admin/token', {
      data: { username, password },
      headers: { 'Content-Type': 'application/json' },
    });
    if (res.ok()) {
      return (await res.json()) as string;
    }
    if (attempt === 0) {
      await new Promise((r) => setTimeout(r, 5000));
      continue;
    }
    throw new Error(
      `Admin token request failed (${res.status()}). ` +
        `Set MAGENTO_ADMIN_USERNAME / MAGENTO_ADMIN_PASSWORD to a working admin, ` +
        `or run bin/magento admin:user:create first. Response: ${await res.text()}`,
    );
  }
  throw new Error('unreachable');
}

export async function seedProduct(opts: SeedOptions = {}): Promise<SeededProduct> {
  const sku = opts.sku ?? `pw-cf-${Date.now()}`;
  const apiURL = opts.apiURL ?? process.env.MAGENTO_API_URL ?? 'http://localhost:8080';
  const { username, password } = ensureTestAdmin();

  const apiContext = await request.newContext({ baseURL: apiURL });
  const token = await getAdminToken(apiContext, username, password);

  const productPayload = {
    product: {
      sku,
      name: `Playwright Cloudflare Test Product ${sku}`,
      price: 19.99,
      status: 1,
      visibility: 4,
      type_id: 'simple',
      attribute_set_id: 4,
      weight: 1,
      extension_attributes: {
        website_ids: [1],
        stock_item: { qty: 100, is_in_stock: true },
      },
      custom_attributes: [
        { attribute_code: 'url_key', value: sku },
        { attribute_code: 'tax_class_id', value: '2' },
        { attribute_code: 'description', value: 'Playwright fixture product.' },
        { attribute_code: 'short_description', value: 'Playwright fixture.' },
      ],
    },
  };

  const createRes = await apiContext.post('/rest/V1/products', {
    headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
    data: productPayload,
  });
  if (!createRes.ok()) {
    throw new Error(`Product create failed (${createRes.status()}): ${await createRes.text()}`);
  }

  return { sku, path: `/${sku}.html`, token, apiContext };
}

export async function deleteProduct(product: SeededProduct): Promise<void> {
  try {
    await product.apiContext.delete(`/rest/V1/products/${product.sku}`, {
      headers: { Authorization: `Bearer ${product.token}` },
    });
  } finally {
    await product.apiContext.dispose();
  }
}
