// assets/js/player.js
// Inicilizimi i VideoJS + preroll

document.addEventListener('DOMContentLoaded', function () {
    if (typeof videojs === 'undefined') return;
    if (!window.NEWFILMA_PLAYER_CONFIG) return;

    var cfg = window.NEWFILMA_PLAYER_CONFIG;

    // Gjej videon (përdor të njëjtin id në movie.php dhe series_watch.php)
    var playerEl =
        document.getElementById('movie-player') ||
        document.getElementById('series-player');

    if (!playerEl) return;

    var player = videojs(playerEl);

    player.ready(function () {
        player.autoplay(cfg.autoplay === 1);
        player.muted(cfg.muted === 1);
        player.preload(cfg.preload || 'auto');

        if (cfg.poster_default && !player.poster()) {
            player.poster(cfg.poster_default);
        }

        // Nëse ke ndonjë API të Nuevoplayer për logo/skin, mund ta thërrasësh këtu
        // p.sh. if (cfg.logo_url) nuevoLogo(player, cfg.logo_url);
    });

    // PREROLL
    if (cfg.preroll_enabled === 1 && cfg.preroll_url) {
        var mainSrc = player.currentSrc();

        // krijo butonin SKIP
        var skipBtn = document.createElement('button');
        skipBtn.textContent = 'Skip';
        skipBtn.style.position = 'absolute';
        skipBtn.style.right = '15px';
        skipBtn.style.bottom = '60px';
        skipBtn.style.zIndex = '9999';
        skipBtn.style.padding = '6px 10px';
        skipBtn.style.borderRadius = '4px';
        skipBtn.style.border = 'none';
        skipBtn.style.background = 'rgba(0,0,0,0.7)';
        skipBtn.style.color = '#fff';
        skipBtn.style.cursor = 'pointer';
        skipBtn.style.display = 'none';

        playerEl.parentNode.style.position = 'relative';
        playerEl.parentNode.appendChild(skipBtn);

        skipBtn.addEventListener('click', function () {
            playMain();
        });

        function playMain () {
            skipBtn.style.display = 'none';
            player.src({ src: mainSrc, type: 'video/mp4' });
            player.play();
        }

        player.src({ src: cfg.preroll_url, type: 'video/mp4' });
        player.play();

        player.on('timeupdate', function () {
            if (player.currentTime() >= (cfg.preroll_skip_after || 5)) {
                skipBtn.style.display = 'block';
            }
        });

        player.on('ended', function () {
            if (player.currentSrc() === cfg.preroll_url) {
                playMain();
            }
        });
    }
});
