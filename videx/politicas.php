<?php
?><link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet">

<style>
.privacy-page {
    --bg-main: #f2f7f5;
    --bg-card: #ffffff;
    --text-main: #102a24;
    --text-soft: #47635c;
    --line: #cfe2da;
    --accent: #0b8f68;
    --accent-dark: #066148;
    --hero-a: #0f766e;
    --hero-b: #115e59;
    font-family: 'Manrope', sans-serif;
    color: var(--text-main);
    background:
        radial-gradient(circle at 10% 5%, rgba(15, 118, 110, 0.15), rgba(15, 118, 110, 0) 40%),
        radial-gradient(circle at 88% 18%, rgba(11, 143, 104, 0.18), rgba(11, 143, 104, 0) 42%),
        var(--bg-main);
    padding: 26px 14px 40px;
}

.privacy-shell {
    max-width: 1080px;
    margin: 0 auto;
    display: grid;
    gap: 16px;
}

.privacy-hero {
    display: grid;
    grid-template-columns: minmax(220px, 360px) 1fr;
    background: linear-gradient(130deg, var(--hero-a), var(--hero-b));
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 18px 46px rgba(16, 42, 36, 0.22);
}

.privacy-hero-media {
    min-height: 260px;
    position: relative;
}

.privacy-hero-media::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(6, 97, 72, 0.08), rgba(6, 97, 72, 0.38));
}

.privacy-hero-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.privacy-hero-content {
    padding: 30px 30px 28px;
    color: #eafff8;
}

.privacy-kicker {
    margin: 0 0 8px;
    font-size: 12px;
    letter-spacing: .14em;
    text-transform: uppercase;
    font-weight: 800;
    color: #bbf7d0;
}

.privacy-title {
    margin: 0;
    font-size: clamp(24px, 3vw, 35px);
    line-height: 1.1;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.privacy-subtitle {
    margin: 14px 0 0;
    max-width: 740px;
    font-size: 15px;
    line-height: 1.55;
    color: #d1fae5;
}

.privacy-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
}

.privacy-card {
    background: var(--bg-card);
    border: 1px solid var(--line);
    border-radius: 18px;
    padding: 18px 18px 16px;
    box-shadow: 0 10px 24px rgba(12, 38, 33, 0.08);
}

.privacy-card h3 {
    margin: 0 0 8px;
    font-size: 16px;
    color: var(--accent-dark);
    font-weight: 800;
}

.privacy-card p {
    margin: 0;
    font-size: 14px;
    line-height: 1.55;
    color: var(--text-soft);
}

.privacy-note {
    background: #ebfff7;
    border: 1px solid #b7ecd8;
    color: #104535;
    border-radius: 14px;
    padding: 14px 16px;
    font-size: 13px;
    line-height: 1.5;
}

@media (max-width: 980px) {
    .privacy-hero {
        grid-template-columns: 1fr;
    }

    .privacy-hero-media {
        min-height: 220px;
    }

    .privacy-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .privacy-page {
        padding: 18px 10px 28px;
    }

    .privacy-hero-content {
        padding: 22px 18px;
    }

    .privacy-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<section class="privacy-page" aria-label="Politicas de privacidad">
    <div class="privacy-shell">
        <header class="privacy-hero">
            <div class="privacy-hero-media">
                <img src="images/publicidad/politicas.jpg" alt="Seguridad de la informacion">
            </div>
            <div class="privacy-hero-content">
                <p class="privacy-kicker">Confidencialidad y confianza</p>
                <h1 class="privacy-title">Politicas de privacidad de <a href="https://<?=$gloEmpresa; ?>" target="_blank" style="color: #1f2937; text-decoration: none;"><?=$gloEmpresa; ?></a></h1>
                <p class="privacy-subtitle">
                    Para <a href="https://<?=$gloEmpresa; ?>" target="_blank" style="color: #2563eb; text-decoration: none; font-weight: 600;"><?=$gloEmpresa; ?></a>, la seguridad de la informacion de nuestros clientes es primordial.
                    Por eso mantenemos politicas de confidencialidad claras para proteger la privacidad de su empresa
                    cuando utiliza nuestros servicios.
                </p>
            </div>
        </header>

        <div class="privacy-grid">
            <article class="privacy-card">
                <h3>Acceso y control de datos</h3>
                <p>
                    Trabajamos de buena fe para facilitarle acceso a su informacion personal y permitir su correccion
                    o eliminacion cuando sea solicitado y sea legalmente procedente.
                </p>
            </article>

            <article class="privacy-card">
                <h3>Validacion de identidad</h3>
                <p>
                    Antes de procesar una solicitud, podemos requerir identificacion del titular y precision de la
                    informacion solicitada para proteger la seguridad de todos los usuarios.
                </p>
            </article>

            <article class="privacy-card">
                <h3>Limitaciones razonables</h3>
                <p>
                    Podremos negar solicitudes repetitivas, tecnicamente desproporcionadas, que afecten la privacidad
                    de terceros o que no sean viables por el tipo de respaldo o almacenamiento.
                </p>
            </article>

            <article class="privacy-card">
                <h3>Conservacion legal y operativa</h3>
                <p>
                    En algunos casos, los datos deben conservarse por cumplimiento normativo o por necesidades
                    empresariales legitimas, incluso cuando se solicite su eliminacion.
                </p>
            </article>

            <article class="privacy-card">
                <h3>Costos del proceso</h3>
                <p>
                    El acceso y la correccion de informacion se gestionan sin costo, salvo cuando la solicitud implique
                    un esfuerzo tecnico u operativo extraordinario.
                </p>
            </article>

            <article class="privacy-card">
                <h3>Canales complementarios</h3>
                <p>
                    Algunos servicios pueden aplicar procedimientos especificos para administrar datos personales,
                    detallados en avisos de privacidad o secciones de preguntas frecuentes.
                </p>
            </article>
        </div>

        <div class="privacy-note">
            Esta politica puede actualizarse para reflejar cambios legales, operativos o de seguridad. Se recomienda
            revisar este apartado de forma periodica.
        </div>
    </div>
</section>
