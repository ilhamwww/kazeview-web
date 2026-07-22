import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';

// Use stealth plugin
puppeteer.use(StealthPlugin());

const url = process.argv[2];

if (!url) {
    console.error('Error: URL parameter is required.');
    process.exit(1);
}

(async () => {
    let browser;
    try {
        browser = await puppeteer.launch({
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-blink-features=AutomationControlled'
            ]
        });

        const page = await browser.newPage();

        // Configure viewport
        await page.setViewport({ width: 1280, height: 800 });

        // Set realistic headers
        await page.setExtraHTTPHeaders({
            'Accept-Language': 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7'
        });

        // Navigate to URL
        await page.goto(url, {
            waitUntil: 'networkidle2',
            timeout: 30000
        });

        // Wait a small extra buffer if Cloudflare challenge is present
        const title = await page.title();
        if (title.includes('Just a moment...') || title.includes('Attention Required!') || title.includes('Cloudflare')) {
            // Wait up to 8 seconds for automatic challenge resolution
            await new Promise(resolve => setTimeout(resolve, 8000));
        }

        // Wait for page body to ensure we have content
        await page.waitForSelector('body', { timeout: 10000 });

        // Output HTML content to stdout
        const html = await page.content();
        process.stdout.write(html);

    } catch (error) {
        console.error('Puppeteer scraping error:', error.message);
        process.exit(1);
    } finally {
        if (browser) {
            await browser.close();
        }
    }
})();