<?php
/**
 * VIDEX Dashboard - Modern Welcome Screen
 * Responsive, light mode, CSS-only design
 *
 * @package VIDEX
 * @version 2.0
 */

$nombreUsuario = htmlspecialchars(explode(' ', trim($_SESSION["nombre"]))[0]);
include_once('acceso_modulos.php');
?>

<style>
    /* Dashboard Styles */
    .dashboard-container {
        padding: 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }

    .dashboard-hero {
        display: grid;
        grid-template-columns: 1fr;
        gap: 3rem;
        align-items: center;
        margin-bottom: 4rem;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        border-radius: 16px;
        padding: 3rem;
        color: #ffffff;
        box-shadow: 0 15px 35px rgba(37, 99, 235, 0.2);
    }

    .dashboard-hero-content {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .dashboard-hero-title {
        font-size: 32px;
        font-weight: 800;
        margin: 0;
        line-height: 1.2;
        color: #ffffff !important;
    }

    .dashboard-hero-subtitle {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
        opacity: 0.95;
        color: #ffffff !important;
    }

    .dashboard-hero-subtitle a {
        color: #ffffff !important;
    }

    .dashboard-hero-welcome {
        font-size: 16px;
        font-weight: 600;
        margin: 1rem 0 0 0;
        line-height: 1.5;
        color: #ffffff !important;
    }

    .dashboard-hero-welcome strong {
        color: #ffffff !important;
    }

    .dashboard-hero-description {
        font-size: 14px;
        margin: 0;
        opacity: 0.9;
        line-height: 1.6;
        color: #ffffff !important;
    }

    .dashboard-hero-content p {
        color: #ffffff !important;
    }

    .dashboard-hero-content a {
        color: #ffffff !important;
    }

    .dashboard-hero-image {
        display: none;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.1);
        padding: 2rem;
        backdrop-filter: blur(10px);
    }

    .dashboard-hero-image img {
        max-width: 100%;
        height: auto;
        filter: drop-shadow(0 0 20px rgba(255, 255, 255, 0.2));
    }

    .dashboard-features {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .feature-card {
        background-color: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 2rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        cursor: pointer;
    }

    .feature-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        border-color: #2563eb;
    }

    .feature-icon {
        font-size: 32px;
        color: #2563eb;
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #dbeafe;
        border-radius: 8px;
    }

    .feature-card.sms .feature-icon {
        color: #16a34a;
        background-color: #dcfce7;
    }

    .feature-card.email .feature-icon {
        color: #d97706;
        background-color: #fef3c7;
    }

    .feature-card.whatsapp .feature-icon {
        color: #0891b2;
        background-color: #cffafe;
    }

    .feature-title {
        font-size: 16px;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }

    .feature-description {
        font-size: 13px;
        color: #6b7280;
        margin: 0;
        line-height: 1.5;
    }

    .feature-link {
        font-size: 12px;
        color: #2563eb;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.5rem;
        transition: gap 0.2s ease;
    }

    .feature-link:hover {
        gap: 0.75rem;
    }

    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .stat-card {
        background-color: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .stat-value {
        font-size: 28px;
        font-weight: 800;
        color: #2563eb;
        margin: 0;
    }

    .stat-label {
        font-size: 12px;
        color: #6b7280;
        margin-top: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .dashboard-section-title {
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .dashboard-section-title i {
        color: #2563eb;
        font-size: 24px;
    }

    .dashboard-cta {
        background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        color: #ffffff;
        margin-bottom: 3rem;
    }

    .dashboard-cta-title {
        font-size: 18px;
        font-weight: 700;
        margin: 0 0 0.5rem 0;
    }

    .dashboard-cta-text {
        font-size: 14px;
        margin: 0 0 1.5rem 0;
        opacity: 0.95;
    }

    .dashboard-cta-button {
        display: inline-block;
        padding: 10px 24px;
        background-color: #ffffff;
        color: #16a34a;
        text-decoration: none;
        border-radius: 6px;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
    }

    .dashboard-cta-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .dashboard-grid-lg {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
    }

    .dashboard-card-lg {
        background-color: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .dashboard-card-lg-title {
        font-size: 16px;
        font-weight: 700;
        color: #1f2937;
        margin: 0 0 1rem 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .dashboard-card-lg-title i {
        color: #2563eb;
    }

    .dashboard-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .dashboard-list li {
        padding: 0.75rem 0;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
    }

    .dashboard-list li:last-child {
        border-bottom: none;
    }

    .dashboard-list-icon {
        color: #2563eb;
        margin-right: 0.75rem;
    }

    .badge-small {
        display: inline-block;
        padding: 4px 8px;
        background-color: #dbeafe;
        color: #1e40af;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .dashboard-container {
            padding: 1.5rem;
        }

        .dashboard-hero {
            grid-template-columns: 1fr;
            gap: 2rem;
            padding: 2rem;
        }

        .dashboard-grid-lg {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .dashboard-container {
            padding: 1rem;
        }

        .dashboard-hero {
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .dashboard-hero-title {
            font-size: 24px;
            color: #ffffff !important;
        }

        .dashboard-hero-subtitle {
            font-size: 16px;
            color: #ffffff !important;
        }

        .dashboard-hero-welcome {
            color: #ffffff !important;
        }

        .dashboard-hero-description {
            color: #ffffff !important;
        }

        .dashboard-hero-content p {
            color: #ffffff !important;
        }

        .dashboard-hero-content a {
            color: #ffffff !important;
        }

        .dashboard-features {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .feature-card {
            padding: 1.5rem;
        }

        .dashboard-stats {
            grid-template-columns: repeat(2, 1fr);
        }

        .dashboard-cta {
            padding: 1.5rem;
        }
    }

    @media (max-width: 480px) {
        .dashboard-container {
            padding: 0.75rem;
        }

        .dashboard-hero {
            padding: 1.25rem;
            border-radius: 12px;
        }

        .dashboard-hero-title {
            font-size: 20px;
            color: #ffffff !important;
        }

        .dashboard-hero-subtitle {
            font-size: 14px;
            color: #ffffff !important;
        }

        .dashboard-hero-welcome {
            color: #ffffff !important;
        }

        .dashboard-hero-description {
            color: #ffffff !important;
        }

        .dashboard-hero-content p {
            color: #ffffff !important;
        }

        .dashboard-hero-content a {
            color: #ffffff !important;
        }

        .feature-card {
            padding: 1rem;
        }

        .dashboard-stats {
            grid-template-columns: 1fr;
        }

        .stat-value {
            font-size: 24px;
        }
    }
</style>

<div class="dashboard-container">
    <?php if (isset($_GET['sin_permiso'])): ?>
    <div role="alert" style="display:flex;align-items:center;gap:.6rem;padding:12px 16px;margin-bottom:1.5rem;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #dc2626;color:#7f1d1d;font-size:13px;">
        <i class="bi bi-shield-exclamation"></i>
        <span>No tiene permiso para acceder a ese módulo. Si lo necesita, solicítelo a un administrador.</span>
    </div>
    <?php endif; ?>
    <!-- HERO SECTION -->
    <div class="dashboard-hero">
        <div class="dashboard-hero-content">
            <h1 class="dashboard-hero-title" style="color: #ffffff !important;"><?=$gloPrograma; ?></h1>
            <p class="dashboard-hero-subtitle" style="color: #ffffff !important;"><a href="https://<?=$gloEmpresa; ?>" target="_blank" style="color: #ffffff !important; text-decoration: none; font-weight: 600;"><?=$gloEmpresa; ?></a></p>
            <p class="dashboard-hero-welcome" style="color: #ffffff !important;">¡Bienvenid@ <strong style="color: #ffffff !important;"><?=$nombreUsuario; ?></strong>!</p>
            <p class="dashboard-hero-description" style="color: #ffffff !important;">
                A la plataforma de VIDEX, tu solución completa para mensajería masiva. Desde aquí puedes procesar, analizar e informar con tan solo un clic de distancia.
            </p>
        </div>
        <div class="dashboard-hero-image">
            <img src="images/publicidad/chatbot.png" alt="VIDEX Chatbot">
        </div>
    </div>

    <!-- MAIN FEATURES -->
    <div>
        <h2 class="dashboard-section-title">
            <i class="bi bi-lightning-fill"></i>
            Funcionalidades Principales
        </h2>
        <div class="dashboard-features">
            <!-- SMS -->
            <div class="feature-card sms">
                <div class="feature-icon">
                    <i class="bi bi-chat-dots"></i>
                </div>
                <h3 class="feature-title">SMS Masivos</h3>
                <p class="feature-description">
                    Envía mensajes SMS a miles de contactos instantáneamente con nuestra plataforma confiable.
                </p>
                <?php if (videx_doc_permitido('prueba_sms')): ?>
                <a href="index.php?doc=prueba_sms&opc=1" class="feature-link">
                    Enviar SMS
                    <i class="bi bi-arrow-right"></i>
                </a>
                <?php else: ?>
                <span class="feature-link" style="opacity:.55;cursor:not-allowed;">
                    Sin acceso
                    <i class="bi bi-lock-fill"></i>
                </span>
                <?php endif; ?>
            </div>

            <!-- EMAIL -->
            <div class="feature-card email">
                <div class="feature-icon">
                    <i class="bi bi-envelope"></i>
                </div>
                <h3 class="feature-title">Email Marketing</h3>
                <p class="feature-description">
                    Campañas de email profesionales y automatizadas para llegar a tu audiencia.
                </p>
                <?php if (videx_doc_permitido('mail_envio')): ?>
                <a href="index.php?doc=mail_envio&opc=1" class="feature-link">
                    Enviar Email
                    <i class="bi bi-arrow-right"></i>
                </a>
                <?php else: ?>
                <span class="feature-link" style="opacity:.55;cursor:not-allowed;">
                    Sin acceso
                    <i class="bi bi-lock-fill"></i>
                </span>
                <?php endif; ?>
            </div>

            <!-- WHATSAPP -->
            <div class="feature-card whatsapp">
                <div class="feature-icon">
                    <i class="bi bi-chat-left-dots"></i>
                </div>
                <h3 class="feature-title">WhatsApp Business</h3>
                <p class="feature-description">
                    Comunícate con tus clientes a través de WhatsApp de forma integrada y profesional.
                </p>
                <a href="#" class="feature-link">
                    Próximamente
                    <i class="bi bi-lock-fill"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- QUICK STATS -->
    <div style="margin-top: 3rem;">
        <h2 class="dashboard-section-title">
            <i class="bi bi-graph-up"></i>
            Tu Actividad
        </h2>
        <div class="dashboard-stats">
            <div class="stat-card">
                <p class="stat-value" id="stat-mensajes-hoy">-</p>
                <p class="stat-label">Mensajes Hoy</p>
            </div>
            <div class="stat-card">
                <p class="stat-value" id="stat-campanas-activas">-</p>
                <p class="stat-label">Campañas Activas</p>
            </div>
            <div class="stat-card">
                <p class="stat-value" id="stat-contactos-total">-</p>
                <p class="stat-label">Contactos</p>
            </div>
            <div class="stat-card">
                <p class="stat-value" id="stat-entregas-total">-</p>
                <p class="stat-label">Entregas</p>
            </div>
        </div>
    </div>

    <!-- CTA SECTION -->
    <div style="margin-top: 3rem;">
        <div class="dashboard-cta">
            <h3 class="dashboard-cta-title">
                <i class="bi bi-star-fill"></i>
                ¿Necesitas ayuda?
            </h3>
            <p class="dashboard-cta-text">
                Consulta nuestra documentación y tutoriales para aprovechar al máximo VIDEX.
            </p>
            <a href="#" class="dashboard-cta-button">Ir a Ayuda</a>
        </div>
    </div>

    <!-- QUICK LINKS -->
    <div style="margin-top: 3rem; margin-bottom: 2rem;">
        <div class="dashboard-grid-lg">
            <div class="dashboard-card-lg">
                <h3 class="dashboard-card-lg-title">
                    <i class="bi bi-star"></i>
                    Acciones Rápidas
                </h3>
                <ul class="dashboard-list">
                    <?php if (videx_doc_permitido('mail_envio')): ?>
                    <li>
                        <a href="index.php?doc=mail_envio&opc=1" style="text-decoration: none; color: inherit; display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <span><i class="bi bi-pencil-square dashboard-list-icon"></i>Enviar Email</span>
                            <span class="badge-small">Email</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (videx_doc_permitido('prueba_sms')): ?>
                    <li>
                        <a href="index.php?doc=prueba_sms&opc=1" style="text-decoration: none; color: inherit; display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <span><i class="bi bi-chat-dots dashboard-list-icon"></i>Enviar SMS</span>
                            <span class="badge-small">SMS</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (videx_doc_permitido('contactos')): ?>
                    <li>
                        <a href="index.php?doc=contactos&opc=2" style="text-decoration: none; color: inherit; display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <span><i class="bi bi-people dashboard-list-icon"></i>Gestionar Contactos</span>
                            <span class="badge-small">CRM</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (videx_doc_permitido('cotizacion')): ?>
                    <li>
                        <a href="index.php?doc=cotizacion&opc=2&activos=1" style="text-decoration: none; color: inherit; display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <span><i class="bi bi-briefcase dashboard-list-icon"></i>Gestionar Proyectos</span>
                            <span class="badge-small">Proyectos</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (videx_doc_permitido('dashboard')): ?>
                    <li>
                        <a href="index.php?doc=dashboard" style="text-decoration: none; color: inherit; display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <span><i class="bi bi-graph-up dashboard-list-icon"></i>Ver Reportes</span>
                            <span class="badge-small">Analytics</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="dashboard-card-lg">
                <h3 class="dashboard-card-lg-title">
                    <i class="bi bi-info-circle"></i>
                    Información Rápida
                </h3>
                <ul class="dashboard-list">
                    <li>
                        <span>Saldo SMS:</span>
                        <strong style="color: #16a34a;">∞</strong>
                    </li>
                    <li>
                        <span>Saldo Email:</span>
                        <strong style="color: #d97706;">∞</strong>
                    </li>
                    <li>
                        <span>Usuarios Activos:</span>
                        <strong style="color: #2563eb;">1</strong>
                    </li>
                    <li>
                        <span>Últimas Campañas:</span>
                        <strong style="color: #6b7280;">0</strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Load Dashboard Metrics on Page Load
 */
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardMetrics();
    // Refresh every 30 seconds
    setInterval(loadDashboardMetrics, 30000);
});

function loadDashboardMetrics() {
    fetch('get_dashboard_metrics.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update metrics with fetched values
                document.getElementById('stat-mensajes-hoy').textContent = formatNumber(data.mensajes_hoy);
                document.getElementById('stat-campanas-activas').textContent = formatNumber(data.campanas_activas);
                document.getElementById('stat-contactos-total').textContent = formatNumber(data.contactos_total);
                document.getElementById('stat-entregas-total').textContent = formatNumber(data.entregas_total);
            }
        })
        .catch(error => {
            // Show error indicator gracefully
            document.getElementById('stat-mensajes-hoy').textContent = 'Error';
        });
}

/**
 * Format number with thousand separators
 */
function formatNumber(num) {
    if (num === null || num === undefined || isNaN(num)) {
        return '-';
    }

    num = parseInt(num);

    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
}
</script>
