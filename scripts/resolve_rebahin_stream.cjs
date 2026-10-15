const https = require('https');
const http = require('http');
const vm = require('vm');
const fs = require('fs');
const path = require('path');

const UA = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/150.0.0.0 Safari/537.36";
const CACHE_DIR = '/home/web/storage/app';
const PLAYER_CACHE_FILE = path.join(CACHE_DIR, 'juicy_player.js');

function fetchHttp(url, headers = {}) {
    return new Promise((resolve, reject) => {
        const client = url.startsWith('https') ? https : http;
        const req = client.get(url, {
            rejectUnauthorized: false,
            headers: {
                'user-agent': UA,
                'accept-language': 'en-US,en;q=0.9',
                'referer': 'https://rebahinxxi3.mom/',
                ...headers
            },
            timeout: 10000
        }, res => {
            if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
                return fetchHttp(res.headers.location, headers).then(resolve).catch(reject);
            }
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => resolve({ status: res.statusCode, data }));
        });
        req.on('error', reject);
        req.on('timeout', () => { req.destroy(); reject(new Error('Timeout')); });
    });
}

async function getPlayerJs() {
    if (fs.existsSync(PLAYER_CACHE_FILE)) {
        try {
            const stat = fs.statSync(PLAYER_CACHE_FILE);
            // Cache valid for 7 days
            if (Date.now() - stat.mtimeMs < 7 * 86400 * 1000) {
                return fs.readFileSync(PLAYER_CACHE_FILE, 'utf8');
            }
        } catch (e) {}
    }

    try {
        const res = await fetchHttp('https://178.211.139.171/assets/players/jwplayer/player.js');
        if (res.status === 200 && res.data.length > 5000) {
            fs.mkdirSync(CACHE_DIR, { recursive: true });
            fs.writeFileSync(PLAYER_CACHE_FILE, res.data, 'utf8');
            return res.data;
        }
    } catch (e) {}

    // Fallback if existing
    if (fs.existsSync(PLAYER_CACHE_FILE)) {
        return fs.readFileSync(PLAYER_CACHE_FILE, 'utf8');
    }
    throw new Error('Failed to load player.js');
}

async function resolve(input) {
    let embedUrl = input;

    // If slug passed instead of full embed URL
    if (!input.startsWith('http')) {
        const playUrl = `https://rebahinxxi3.mom/${input}/play/`;
        const resPlay = await fetchHttp(playUrl);
        const match = resPlay.data.match(/data-iframe=['"]([^'"]+)['"]/);
        if (!match) throw new Error('data-iframe not found in play page');
        embedUrl = Buffer.from(match[1], 'base64').toString('utf8');
    }

    // Fetch embed HTML
    const embedRes = await fetchHttp(embedUrl, {
        'referer': 'https://rebahinxxi3.mom/'
    });

    const m = embedRes.data.match(/_juicycodes\(([\s\S]*?)\)/);
    if (!m) throw new Error('_juicycodes payload not found in embed');
    const juicyArgCode = m[1];

    const playerJs = await getPlayerJs();

    let extractedConfig = null;
    const mockPlayer = {
        setup: function(cfg) { extractedConfig = cfg; return this; },
        on: () => mockPlayer, once: () => mockPlayer, off: () => mockPlayer, addButton: () => mockPlayer,
        getContainer: () => ({ appendChild: () => {} })
    };
    const sandbox = {
        window: {},
        navigator: { userAgent: UA },
        document: {
            getElementById: () => ({ style: {} }),
            querySelector: () => ({ style: {} }),
            createElement: () => ({ style: {}, setAttribute: () => {}, addEventListener: () => {} }),
            body: { appendChild: () => {}, removeChild: () => {} },
            addEventListener: () => {},
        },
        console: { log: () => {}, warn: () => {}, error: () => {} },
        setTimeout: () => {}, setInterval: () => {},
        jwplayer: () => mockPlayer
    };
    sandbox.window = sandbox;
    sandbox.self = sandbox;

    const ctx = vm.createContext(sandbox);
    vm.runInContext(playerJs, ctx);
    vm.runInContext(`_juicycodes(${juicyArgCode});`, ctx);

    if (!extractedConfig || !extractedConfig.sources || !extractedConfig.sources.file) {
        throw new Error('Failed to extract sources.file from juicy config');
    }

    const rawStreamUrl = extractedConfig.sources.file;
    // Rewrite host to our Nginx stream-relay
    // Example: https://daisy.groovy.monster/stream/... -> https://www.kazeview.com/stream-relay/p/daisy.groovy.monster/stream/...
    const urlObj = new URL(rawStreamUrl);
    const relayHost = 'www.kazeview.com';
    const relayStreamUrl = `https://${relayHost}/stream-relay/p/${urlObj.host}${urlObj.pathname}${urlObj.search}`;

    return {
        success: true,
        video_url: relayStreamUrl,
        raw_url: rawStreamUrl,
        type: 'm3u8',
        image: extractedConfig.image ? `https://${relayHost}/stream-relay/p/${new URL(extractedConfig.image).host}${new URL(extractedConfig.image).pathname}${new URL(extractedConfig.image).search}` : null
    };
}

const arg = process.argv[2];
if (!arg) {
    console.error(JSON.stringify({ success: false, error: 'Argument required' }));
    process.exit(1);
}

resolve(arg)
    .then(res => {
        console.log(JSON.stringify(res));
    })
    .catch(err => {
        console.error(JSON.stringify({ success: false, error: err.message }));
        process.exit(1);
    });
