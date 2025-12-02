<?php
// index.php – faqja kryesore

$page_title  = "Faqja kryesore - NewFilma";
$active_page = "home";

require __DIR__ . '/app/header.php';

// tani auth + current_user() janë ngarkuar nga app/header.php
$user = current_user();
?>

<style>
    .hero {
        display: grid;
        grid-template-columns: minmax(0, 2.2fr) minmax(0, 1.2fr);
        gap: 28px;
        align-items: center;
        margin-bottom: 32px;
    }
    @media (max-width: 800px) {
        .hero {
            grid-template-columns: minmax(0, 1fr);
        }
    }
    .hero-title {
        font-size: 34px;
        font-weight: 800;
        letter-spacing: -0.03em;
        margin-bottom: 10px;
    }
    .hero-sub {
        font-size: 14px;
        color: #9ca3af;
        margin-bottom: 18px;
        max-width: 540px;
    }
    .hero-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 20px;
    }
    .tag-pill {
        font-size: 12px;
        padding: 4px 10px;
        border-radius: 999px;
        border: 1px solid #1f2937;
        background: rgba(15,23,42,0.9);
        color: #e5e7eb;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 10px;
    }
    .btn-main {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 9px 18px;
        border-radius: 999px;
        border: 1px solid transparent;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
        text-decoration: none;
    }
    .btn-main.secondary {
        background: #020617;
        border-color: #1f2937;
        color: #e5e7eb;
        font-weight: 500;
    }
    .btn-main.secondary:hover {
        background: #0f172a;
    }
    .hero-meta {
        font-size: 11px;
        color: #6b7280;
    }

    .cards-row {
        margin-top: 10px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 16px;
    }
    .card {
        background: radial-gradient(circle at top, #020617, #020617 40%, #000 100%);
        border-radius: 18px;
        border: 1px solid #0f172a;
        padding: 14px 14px 12px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        box-shadow: 0 18px 40px rgba(15,23,42,0.95);
    }
    .card-header {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .card-icon {
        width: 32px;
        height: 32px;
        border-radius: 999px;
        background: #020617;
        border: 1px solid #1f2937;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    .card-title {
        font-size: 15px;
        font-weight: 600;
    }
    .card-desc {
        font-size: 12px;
        color: #9ca3af;
    }
    .card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-top: 4px;
    }
    .card-btn {
        font-size: 12px;
        padding: 6px 11px;
        border-radius: 999px;
        border: 1px solid #1f2937;
        background: #020617;
        color: #e5e7eb;
        text-decoration: none;
    }
    .card-btn:hover {
        background: #0f172a;
    }
    .card-btn.primary {
        border-color: #22c55e;
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: #0b1120;
        font-weight: 600;
    }
    .card-meta {
        font-size: 10px;
        color: #6b7280;
    }
</style>

<section class="hero">
    <div>
        <h1 class="hero-title">Filma, seriale & TV Live në një vend.</h1>
        <p class="hero-sub">
            NewFilma të lejon të shikosh filmat dhe serialet e preferuara,
            dhe kanalet TV live – nga çdo pajisje.
        </p>

        <div class="hero-tags">
            <span class="tag-pill">⭐ Më të vlerësuarit</span>
            <span class="tag-pill">🎬 Filma HD / FHD</span>
            <span class="tag-pill">📺 Serialet më të fundit</span>
            <span class="tag-pill">📡 TV Live</span>
        </div>

        <div class="hero-actions">
            <a href="movies.php" class="btn-main">▶ Shiko filmat</a>
            <a href="series.php" class="btn-main secondary">📺 Shiko serialet</a>
            <a href="tvlive.php" class="btn-main secondary">📡 TV Live</a>
        </div>

        <div class="hero-meta">
            Për të parë përmbajtjen e plotë duhet të jesh i regjistruar dhe me abonim aktiv.
        </div>
    </div>

    <div>
        <div class="card" style="min-height:140px;">
            <div class="card-header">
                <div class="card-icon">🔐</div>
                <div>
                    <div class="card-title">Hyr / Regjistrohu</div>
                    <div class="card-desc">
                        Krijo llogari ose hyr me përdoruesin tënd për të parë gjithë bibliotekën.
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <?php if (!$user): ?>
                    <div style="display:flex;gap:8px;">
                        <a class="card-btn primary" href="login.php">Hyr tani</a>
                        <a class="card-btn" href="register.php">Regjistrohu</a>
                    </div>
                <?php else: ?>
                    <div class="card-meta">
                        I loguar si: <strong><?= htmlspecialchars($user['name'] ?? 'Përdorues') ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="cards-row">
    <div class="card">
        <div class="card-header">
            <div class="card-icon">🎬</div>
            <div>
                <div class="card-title">Filma</div>
                <div class="card-desc">
                    Shfleto katalogun e plotë të filmave dhe hap direkt player-in.
                </div>
            </div>
        </div>
        <div class="card-footer">
            <a class="card-btn primary" href="movies.php">Hap filmat →</a>
            <div class="card-meta">Lexon nga <strong>movies.json</strong>.</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-icon">📺</div>
            <div>
                <div class="card-title">Serialet</div>
                <div class="card-desc">
                    Zbulo serialet sipas sezoneve, viteve dhe IMDb.
                </div>
            </div>
        </div>
        <div class="card-footer">
            <a class="card-btn primary" href="series.php">Hap serialet →</a>
            <div class="card-meta">Lexon nga <strong>series.json</strong>.</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-icon">📡</div>
            <div>
                <div class="card-title">TV Live</div>
                <div class="card-desc">
                    Kanale televizive live nga serveri yt.
                </div>
            </div>
        </div>
        <div class="card-footer">
            <a class="card-btn primary" href="tvlive.php">Hap TV Live →</a>
            <div class="card-meta">Lexon nga <strong>tv.json</strong>.</div>
        </div>
    </div>
</section>

<?php
require __DIR__ . '/app/footer.php';
?>
