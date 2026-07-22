<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RaptDramaHlsProxyTest extends TestCase
{
    public function test_signed_bunny_manifest_preserves_token_for_relative_segments(): void
    {
        $manifestUrl = 'https://vz-6341da99-512.b-cdn.net/drama-id/playlist.m3u8'
            . '?bcdn_token=test-token'
            . '&expires=1784086905'
            . '&token_path=%2Fdrama-id%2F';

        Http::fake(function (Request $request) use ($manifestUrl) {
            $this->assertSame($manifestUrl, $request->url());

            return Http::response(
                "#EXTM3U\n#EXTINF:4.000,\nsegment-0001.ts\n",
                200,
                ['Content-Type' => 'application/vnd.apple.mpegurl'],
            );
        });

        $response = $this->get('/api/proxy-hls?url=' . urlencode($manifestUrl));

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.apple.mpegurl; charset=utf-8')
            ->assertSee('segment-0001.ts', false);

        $expectedSegmentUrl = 'https://vz-6341da99-512.b-cdn.net/drama-id/segment-0001.ts'
            . '?bcdn_token=test-token'
            . '&expires=1784086905'
            . '&token_path=%2Fdrama-id%2F';

        $this->assertStringContainsString(
            urlencode($expectedSegmentUrl),
            $response->getContent(),
        );
    }

    public function test_audio_rendition_playlist_is_proxied_via_hls(): void
    {
        $manifestUrl = 'https://vz-6341da99-512.b-cdn.net/drama-id/playlist.m3u8'
            . '?bcdn_token=test-token'
            . '&expires=1784086905'
            . '&token_path=%2Fdrama-id%2F';

        Http::fake([
            $manifestUrl => Http::response(
                "#EXTM3U\n"
                . "#EXT-X-MEDIA:TYPE=AUDIO,GROUP-ID=\"audio\",URI=\"audio.m3u8\"\n"
                . "#EXT-X-STREAM-INF:BANDWIDTH=800000,AUDIO=\"audio\"\n"
                . "video.m3u8\n",
                200,
                ['Content-Type' => 'application/vnd.apple.mpegurl'],
            ),
        ]);

        $response = $this->get('/api/proxy-hls?url=' . urlencode($manifestUrl));

        $response->assertOk();

        $expectedAudioPlaylistUrl = 'https://vz-6341da99-512.b-cdn.net/drama-id/audio.m3u8'
            . '?bcdn_token=test-token'
            . '&expires=1784086905'
            . '&token_path=%2Fdrama-id%2F';

        // Audio playlist harus lewat proxy-hls (bukan proxy-stream) agar segmennya ikut di-rewrite.
        $this->assertStringContainsString(
            'URI="' . url('/api/proxy-hls?url=' . urlencode($expectedAudioPlaylistUrl)) . '"',
            $response->getContent(),
        );

        // Video playlist tetap direkursi lewat proxy-hls.
        $expectedVideoPlaylistUrl = 'https://vz-6341da99-512.b-cdn.net/drama-id/video.m3u8'
            . '?bcdn_token=test-token'
            . '&expires=1784086905'
            . '&token_path=%2Fdrama-id%2F';

        $this->assertStringContainsString(
            url('/api/proxy-hls?url=' . urlencode($expectedVideoPlaylistUrl)),
            $response->getContent(),
        );
    }
}
