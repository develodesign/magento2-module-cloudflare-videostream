import { test, expect, type Page } from '@playwright/test';
import { seedProduct, deleteProduct, type SeededProduct } from './fixtures/product-fixture';

/**
 * E2E coverage for Develo_CloudflareVideo.
 *
 * Inner-loop logic is covered by PHPUnit. Playwright is the outer-loop
 * confirmation that the Hyvä gallery override loads, the Alpine JS still
 * parses cleanly, and a Cloudflare slide produces an iframe with the
 * expected `customer-<code>.cloudflarestream.com/<UID>/iframe` URL when
 * the user clicks play.
 *
 * The PDP suite seeds a simple product via Magento's REST API before the
 * tests run and deletes it afterwards, so the suite is self-contained and
 * does not require seeded catalog data.
 *
 * Override `PLAYWRIGHT_PDP_PATH` to skip seeding and reuse an existing PDP
 * (e.g. when iterating against a populated staging environment).
 */

const TEST_UID = '31c9291ab41fac05471db4e73aa11717';
const TEST_CUSTOMER_CODE = 'testcode123';

async function clickThroughCodespaceInterstitial(page: Page): Promise<void> {
  const continueBtn = page
    .getByRole('link', { name: /^Continue$/i })
    .or(page.getByRole('button', { name: /^Continue$/i }));
  if (await continueBtn.first().isVisible().catch(() => false)) {
    await continueBtn.first().click();
    await page.waitForLoadState('domcontentloaded');
  }
}

test.describe('Cloudflare video — module smoke', () => {
  test('homepage still loads after Develo_CloudflareVideo is enabled', async ({ page }) => {
    const response = await page.goto('/');
    expect(response?.status(), 'homepage should respond 2xx').toBeLessThan(400);
    await clickThroughCodespaceInterstitial(page);
    await expect(page).toHaveTitle(/.+/);
    await expect(page.locator('body')).toBeVisible();
  });
});

test.describe('Cloudflare video — PDP gallery rendering', () => {
  let pdpPath: string;
  let seeded: SeededProduct | null = null;

  test.beforeAll(async () => {
    if (process.env.PLAYWRIGHT_PDP_PATH) {
      pdpPath = process.env.PLAYWRIGHT_PDP_PATH;
      return;
    }
    seeded = await seedProduct();
    pdpPath = seeded.path;
  });

  test.afterAll(async () => {
    if (seeded) {
      await deleteProduct(seeded);
      seeded = null;
    }
  });

  test('renders a Cloudflare iframe slide when a Cloudflare video is in the gallery', async ({ page }) => {
    const response = await page.goto(pdpPath);
    expect(response?.status(), `PDP ${pdpPath} should respond 2xx`).toBeLessThan(400);
    await clickThroughCodespaceInterstitial(page);

    await expect(page.locator('#gallery')).toBeVisible();
    await expect(page.locator('#cloudflare-player')).toBeAttached();

    // The customer subdomain now comes from the per-slide videoUrl itself
    // (no module config involved), so injecting a synthetic gallery slide
    // with a `customer-<code>.cloudflarestream.com/<UID>/iframe` URL is all
    // that's needed for the storefront JS to embed against that subdomain.
    await page.evaluate(
      ({ uid, code }) => {
        const root = document.getElementById('gallery');
        if (!root) {
          throw new Error('gallery root missing');
        }
        const stack = (root as unknown as { _x_dataStack?: Array<Record<string, unknown>> })._x_dataStack;
        if (!stack || !stack[0]) {
          throw new Error('Alpine data stack not found on #gallery');
        }
        const ctx = stack[0] as {
          images: Array<Record<string, unknown>>;
          setActive: (i: number) => void;
        };
        ctx.images = [
          {
            type: 'video',
            videoUrl: `https://customer-${code}.cloudflarestream.com/${uid}/iframe`,
            img: 'https://example.test/poster.jpg',
            thumb: 'https://example.test/poster.jpg',
            full: 'https://example.test/poster.jpg',
            isMain: true,
            caption: 'Cloudflare test video',
          },
        ];
        ctx.setActive(0);
      },
      { uid: TEST_UID, code: TEST_CUSTOMER_CODE },
    );

    const playBtn = page.getByRole('button', { name: /^Play video$/i });
    await expect(playBtn).toBeVisible();
    await playBtn.click();

    const iframe = page.locator('#cloudflare-player iframe');
    await expect(iframe).toBeAttached();
    const src = await iframe.getAttribute('src');
    expect(src).not.toBeNull();
    expect(src!).toContain(`customer-${TEST_CUSTOMER_CODE}.cloudflarestream.com`);
    expect(src!).toContain(`${TEST_UID}/iframe`);
    expect(src!).toContain('poster=');
  });

  // Note: the bare-UID / non-customer-host fallback path
  // (`iframe.videodelivery.net`) is exercised by `EmbedUrlBuilderTest` in
  // PHPUnit and by an inline regression check inside the override template
  // — running it again as a Playwright spec would just re-exercise the same
  // client-side regex without testing a different code path.
});
