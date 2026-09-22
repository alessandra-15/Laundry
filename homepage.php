<?php
/**
 * homepage.php
 * WashFlow — Laundry Management and Booking System
 *
 * Final homepage. Clear navigation, hindi confusing.
 */

session_start();
$currentYear = date('Y');

// OPTIONAL: I-redirect ang naka-login na user sa tamang dashboard.
// I-uncomment kapag handa na ang login system.
/*
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'staff') {
        header('Location: dashboard.php');
        exit;
    } elseif ($_SESSION['role'] === 'customer') {
        header('Location: userdashboard.php');
        exit;
    }
}
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WashFlow — Laundry Management and Booking System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --dark-blue:       #063452;
            --dark-blue-deep:  #042640;
            --primary:         #005A85;
            --primary-mid:     #0076A8;
            --light-blue:      #A8E8F9;
            --light-blue-soft: #E8F6FC;
            --light-blue-pale: #F2FAFD;

            --yellow:          #FFD93D;
            --yellow-soft:     #FFF9DB;
            --yellow-dark:     #B88A00;

            --bg-light:        #E5EEF5;
            --bg-white:        #FFFFFF;

            --text-primary:    #0A2540;
            --text-secondary:  #5A7184;
            --text-muted:      #94A9B8;

            --border:          #C9DCE8;
            --border-light:    #DCEAF3;

            --shadow-sm: 0 1px 2px rgba(10, 37, 64, 0.04);
            --shadow-md: 0 4px 12px rgba(10, 37, 64, 0.06);
            --shadow-lg: 0 12px 32px rgba(10, 37, 64, 0.08);
            --shadow-xl: 0 24px 48px rgba(10, 37, 64, 0.12);
            --shadow-yellow: 0 6px 16px rgba(255, 217, 61, 0.4);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-primary);
            background-color: var(--bg-light);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5 { font-weight: 700; letter-spacing: -0.02em; color: var(--text-primary); }
        a { text-decoration: none; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-40px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(3deg); }
        }
        @keyframes pulseGlow {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }

        .anim-fade-up { animation: fadeInUp 0.7s ease-out both; }
        .anim-slide-left { animation: slideInLeft 0.7s ease-out both; }
        .anim-delay-1 { animation-delay: 0.1s; }
        .anim-delay-2 { animation-delay: 0.2s; }
        .anim-delay-3 { animation-delay: 0.3s; }

        /* LOGO */
        .wf-logo { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .wf-logo-mark { width: 40px; height: 40px; flex-shrink: 0; }
        .wf-logo-mark svg { width: 100%; height: 100%; filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2)); }
        .wf-logo-text { display: flex; flex-direction: column; line-height: 1; }
        .wf-logo-name { font-size: 1.35rem; font-weight: 800; color: white; letter-spacing: -0.04em; line-height: 1; }
        .wf-logo-name .flow { color: var(--yellow); }
        .wf-logo-tagline { font-size: 0.6rem; font-weight: 500; color: rgba(168, 232, 249, 0.7); letter-spacing: 0.15em; text-transform: uppercase; margin-top: 3px; }

        /* NAVBAR */
        .wf-navbar {
            background: var(--dark-blue-deep);
            border-bottom: 1px solid rgba(168, 232, 249, 0.1);
            padding: 0.75rem 0;
            box-shadow: 0 4px 20px rgba(4, 38, 64, 0.3);
        }

        .wf-navbar .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 0.55rem 1rem !important;
            border-radius: 50px;
            transition: all 0.25s;
            background: transparent;
            position: relative;
        }
        .wf-navbar .nav-link:hover { color: var(--yellow) !important; background: rgba(255, 217, 61, 0.08); }
        .wf-navbar .nav-link.active { color: var(--yellow) !important; background: rgba(255, 217, 61, 0.12); }
        .wf-navbar .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 2px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 2px;
            background: var(--yellow);
            border-radius: 2px;
        }

        .wf-btn-nav-outline {
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.55rem 1.5rem;
            border: 1.5px solid rgba(255, 255, 255, 0.4);
            border-radius: 50px;
            background: transparent;
            transition: all 0.25s;
        }
        .wf-btn-nav-outline:hover {
            border-color: white;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateY(-1px);
        }

        .wf-btn-nav-solid {
            background: var(--yellow);
            color: var(--dark-blue-deep);
            font-weight: 700;
            font-size: 0.875rem;
            padding: 0.55rem 1.75rem;
            border: 1.5px solid var(--yellow);
            border-radius: 50px;
            transition: all 0.25s;
            box-shadow: 0 4px 12px rgba(255, 217, 61, 0.3);
        }
        .wf-btn-nav-solid:hover {
            background: #FFE066;
            border-color: #FFE066;
            color: var(--dark-blue-deep);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(255, 217, 61, 0.5);
        }

        /* HERO */
        .wf-hero {
            position: relative;
            min-height: 88vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, rgba(2, 25, 45, 0.96) 0%, rgba(4, 38, 64, 0.92) 50%, rgba(6, 52, 82, 0.85) 100%),
                        url('https://images.unsplash.com/photo-1545173168-9f1947eebb7f?w=1600') center/cover;
            color: white;
            overflow: hidden;
        }

        .wf-hero::before {
            content: '';
            position: absolute;
            top: -200px;
            right: -200px;
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, rgba(255, 217, 61, 0.12) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            animation: pulseGlow 6s ease-in-out infinite;
        }
        .wf-hero::after {
            content: '';
            position: absolute;
            bottom: -200px;
            left: -200px;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(168, 232, 249, 0.08) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .wf-shape { position: absolute; border-radius: 50%; pointer-events: none; opacity: 0.08; z-index: 1; }
        .wf-shape-1 { width: 200px; height: 200px; background: var(--yellow); top: 15%; right: 8%; animation: float 8s ease-in-out infinite; }
        .wf-shape-2 { width: 120px; height: 120px; background: var(--light-blue); bottom: 20%; left: 5%; animation: float 10s ease-in-out infinite reverse; }

        .wf-hero-content { position: relative; z-index: 2; max-width: 680px; }
        .wf-hero h1 {
            font-size: 4rem;
            font-weight: 800;
            color: white;
            letter-spacing: -0.045em;
            line-height: 1.02;
            margin-bottom: 1.25rem;
            text-shadow: 0 4px 30px rgba(0, 0, 0, 0.4);
        }
        .wf-hero h1 .accent { color: var(--yellow); position: relative; display: inline-block; }
        .wf-hero h1 .accent::after {
            content: '';
            position: absolute;
            bottom: 8px;
            left: 0;
            right: 0;
            height: 4px;
            background: rgba(255, 217, 61, 0.35);
            border-radius: 2px;
            z-index: -1;
        }
        .wf-hero .tagline { font-size: 1.75rem; color: var(--light-blue); font-weight: 600; margin-bottom: 0.875rem; letter-spacing: -0.01em; }
        .wf-hero .sub-tagline { font-size: 1.15rem; color: rgba(255, 255, 255, 0.88); margin-bottom: 2.25rem; line-height: 1.7; max-width: 560px; }

        .wf-hero-buttons { display: flex; gap: 1rem; flex-wrap: wrap; }

        .wf-btn-hero-primary {
            background: var(--yellow);
            color: var(--dark-blue-deep);
            font-weight: 700;
            font-size: 1rem;
            padding: 1rem 2.75rem;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            border: none;
            box-shadow: 0 8px 24px rgba(255, 217, 61, 0.45);
            position: relative;
            overflow: hidden;
        }
        .wf-btn-hero-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }
        .wf-btn-hero-primary:hover::before { left: 100%; }
        .wf-btn-hero-primary:hover {
            background: #FFE066;
            color: var(--dark-blue-deep);
            transform: translateY(-3px);
            box-shadow: 0 14px 32px rgba(255, 217, 61, 0.6);
        }

        .wf-btn-hero-outline {
            background: transparent;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            padding: 1rem 2.75rem;
            border: 2px solid rgba(255, 255, 255, 0.6);
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        .wf-btn-hero-outline:hover {
            background: white;
            color: var(--dark-blue-deep);
            border-color: white;
            transform: translateY(-3px);
            box-shadow: 0 14px 32px rgba(255, 255, 255, 0.25);
        }

        /* SECTIONS */
        .wf-section { padding: 5.5rem 0; }
        .wf-section-header { text-align: center; max-width: 660px; margin: 0 auto 3.5rem; }

        .wf-section-label {
            display: inline-block;
            color: var(--dark-blue);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin-bottom: 1rem;
            position: relative;
            padding: 0 2.5rem;
        }
        .wf-section-label::before, .wf-section-label::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 1.5rem;
            height: 1px;
            background: var(--dark-blue);
            opacity: 0.4;
        }
        .wf-section-label::before { left: 0; }
        .wf-section-label::after { right: 0; }

        .wf-section-title { font-size: 2.5rem; font-weight: 800; color: var(--dark-blue); letter-spacing: -0.035em; line-height: 1.15; margin-bottom: 0.875rem; }
        .wf-section-subtitle { font-size: 1.05rem; color: var(--text-secondary); line-height: 1.7; margin: 0; }

        /* ABOUT */
        .wf-about {
            background: linear-gradient(180deg, #EAF2F8 0%, #F0F5F9 100%);
            border-bottom: 1px solid rgba(10, 37, 64, 0.06);
        }

        .wf-about-images {
            position: relative;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 220px 220px;
            gap: 18px;
            max-width: 100%;
        }

        .wf-about-image {
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 12px 28px rgba(10, 74, 107, 0.12);
            transition: all 0.4s;
            position: relative;
        }
        .wf-about-image::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(0, 107, 153, 0.15) 0%, transparent 60%);
            opacity: 0;
            transition: opacity 0.4s;
            pointer-events: none;
        }
        .wf-about-image:hover::after { opacity: 1; }
        .wf-about-image:hover { transform: translateY(-8px) scale(1.02); box-shadow: 0 24px 48px rgba(10, 74, 107, 0.2); }
        .wf-about-image img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.5s; }
        .wf-about-image:hover img { transform: scale(1.05); }
        .wf-about-image:nth-child(1) { grid-row: span 2; }

        .wf-about-content h2 { font-size: 2.25rem; font-weight: 800; color: var(--dark-blue); letter-spacing: -0.035em; line-height: 1.2; margin-bottom: 1rem; }
        .wf-about-content .lead { font-size: 1.05rem; color: var(--text-secondary); line-height: 1.75; margin-bottom: 2rem; }

        .wf-feature-list { display: flex; flex-direction: column; gap: 1rem; }

        .wf-feature-card {
            display: flex;
            align-items: flex-start;
            gap: 1.25rem;
            background: var(--bg-white);
            border: 1px solid rgba(10, 37, 64, 0.06);
            border-radius: 16px;
            padding: 1.5rem 1.625rem;
            transition: all 0.3s;
            position: relative;
            box-shadow: 0 4px 12px rgba(10, 74, 107, 0.06);
        }
        .wf-feature-card:hover {
            border-color: var(--light-blue);
            box-shadow: 0 16px 32px rgba(10, 74, 107, 0.12);
            transform: translateY(-3px);
        }

        .wf-feature-card-icon {
            width: 54px;
            height: 54px;
            background: linear-gradient(135deg, var(--yellow) 0%, #FFE066 100%);
            color: var(--dark-blue);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
            box-shadow: 0 6px 14px rgba(255, 217, 61, 0.4);
            transition: transform 0.3s;
        }
        .wf-feature-card:hover .wf-feature-card-icon { transform: scale(1.08) rotate(-5deg); box-shadow: 0 8px 20px rgba(255, 217, 61, 0.55); }

        .wf-feature-card-content { flex: 1; min-width: 0; }
        .wf-feature-card h4 { font-size: 1.1rem; font-weight: 700; color: var(--dark-blue); margin-bottom: 0.4rem; letter-spacing: -0.015em; }
        .wf-feature-card p { font-size: 0.9rem; color: var(--text-secondary); margin: 0; line-height: 1.6; }

        /* SERVICES */
        .wf-services {
            background: linear-gradient(180deg, #CFE3EF 0%, #C1DAE9 100%);
            position: relative;
            overflow: hidden;
        }
        .wf-services::before {
            content: '';
            position: absolute;
            top: 10%;
            right: -100px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(168, 232, 249, 0.5) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .wf-services::after {
            content: '';
            position: absolute;
            bottom: 10%;
            left: -100px;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(255, 217, 61, 0.18) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .wf-service-card {
            background: var(--bg-white);
            border: 1px solid rgba(10, 37, 64, 0.06);
            border-radius: 20px;
            overflow: hidden;
            height: 100%;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            display: flex;
            flex-direction: column;
            position: relative;
            box-shadow: 0 6px 20px rgba(10, 74, 107, 0.08);
        }
        .wf-service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--yellow) 0%, var(--light-blue) 100%);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s;
            z-index: 3;
        }
        .wf-service-card:hover::before { transform: scaleX(1); }
        .wf-service-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 32px 64px rgba(10, 74, 107, 0.22);
            border-color: var(--light-blue);
        }

        .wf-service-image {
            position: relative;
            height: 240px;
            overflow: hidden;
            background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary) 100%);
        }
        .wf-service-image img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.7s; }
        .wf-service-card:hover .wf-service-image img { transform: scale(1.1); }
        .wf-service-image::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(4, 38, 64, 0.15) 0%, rgba(4, 38, 64, 0.6) 100%);
            pointer-events: none;
        }

        .wf-service-number {
            position: absolute;
            top: 18px;
            left: 18px;
            width: 48px;
            height: 48px;
            background: var(--yellow);
            color: var(--dark-blue-deep);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 800;
            box-shadow: 0 8px 20px rgba(255, 217, 61, 0.5);
            z-index: 2;
            transition: all 0.3s;
        }
        .wf-service-card:hover .wf-service-number { transform: rotate(-10deg) scale(1.12); box-shadow: 0 12px 28px rgba(255, 217, 61, 0.7); }

        .wf-service-body { padding: 2rem 1.75rem 1.875rem; flex: 1; display: flex; flex-direction: column; position: relative; }

        .wf-service-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--yellow) 0%, #FFE066 100%);
            color: var(--dark-blue);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            margin-bottom: 1.25rem;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(255, 217, 61, 0.4);
            margin-top: -48px;
            position: relative;
            z-index: 2;
        }
        .wf-service-card:hover .wf-service-icon { transform: scale(1.1) rotate(-8deg); box-shadow: 0 12px 28px rgba(255, 217, 61, 0.6); }

        .wf-service-body h3 { font-size: 1.35rem; font-weight: 800; color: var(--dark-blue); margin-bottom: 0.625rem; letter-spacing: -0.02em; transition: color 0.3s; }
        .wf-service-card:hover .wf-service-body h3 { color: var(--primary); }
        .wf-service-body p { font-size: 0.925rem; color: var(--text-secondary); margin: 0; line-height: 1.65; }

        /* PRICING */
        .wf-pricing {
            background: linear-gradient(180deg, #F0F5F9 0%, #EAF2F8 100%);
            border-top: 1px solid rgba(10, 37, 64, 0.06);
            border-bottom: 1px solid rgba(10, 37, 64, 0.06);
        }

        .wf-pricing-accordion .accordion-item {
            border: 1px solid rgba(10, 37, 64, 0.06);
            border-radius: 16px !important;
            margin-bottom: 0.75rem;
            overflow: hidden;
            background: var(--bg-white);
            transition: all 0.25s;
            box-shadow: 0 4px 12px rgba(10, 74, 107, 0.06);
        }
        .wf-pricing-accordion .accordion-item:hover { border-color: var(--light-blue); box-shadow: 0 8px 20px rgba(10, 74, 107, 0.1); }
        .wf-pricing-accordion .accordion-button {
            background: var(--bg-white);
            color: var(--dark-blue);
            font-weight: 700;
            font-size: 1rem;
            padding: 1.25rem 1.5rem;
            border: none;
            box-shadow: none;
            display: flex;
            align-items: center;
            gap: 0.875rem;
        }
        .wf-pricing-accordion .accordion-button:not(.collapsed) {
            background: var(--bg-white);
            color: var(--dark-blue);
            border-bottom: 1px solid var(--border-light);
        }
        .wf-pricing-accordion .accordion-button:focus { box-shadow: none; }
        .wf-pricing-accordion .accordion-button i.category-icon { color: var(--primary); font-size: 0.95rem; width: 20px; text-align: center; transition: transform 0.3s; }
        .wf-pricing-accordion .accordion-button:not(.collapsed) i.category-icon { transform: scale(1.15); }
        .wf-pricing-accordion .accordion-button::after { filter: invert(20%) sepia(40%) saturate(1200%) hue-rotate(180deg); opacity: 0.7; }
        .wf-pricing-accordion .accordion-body { padding: 0; background: var(--bg-white); }

        .wf-pricing-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.125rem 1.5rem;
            border-bottom: 1px solid var(--border-light);
            gap: 1rem;
            transition: background 0.2s;
        }
        .wf-pricing-item:last-child { border-bottom: none; }
        .wf-pricing-item:hover { background: var(--light-blue-pale); }
        .wf-pricing-item-info h5 { font-size: 0.95rem; font-weight: 700; color: var(--dark-blue); margin-bottom: 0.25rem; letter-spacing: -0.01em; }
        .wf-pricing-item-info p { font-size: 0.85rem; color: var(--text-secondary); margin: 0; line-height: 1.5; }
        .wf-pricing-item-price {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--yellow-dark);
            background: var(--yellow-soft);
            padding: 0.4rem 0.875rem;
            border-radius: 50px;
            white-space: nowrap;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        /* FAQ */
        .wf-faq {
            background: linear-gradient(180deg, #C1DAE9 0%, #B5D2E3 100%);
            position: relative;
            overflow: hidden;
        }
        .wf-faq::before {
            content: '';
            position: absolute;
            top: 20%;
            left: -120px;
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, rgba(168, 232, 249, 0.4) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .wf-faq::after {
            content: '';
            position: absolute;
            bottom: 15%;
            right: -100px;
            width: 280px;
            height: 280px;
            background: radial-gradient(circle, rgba(255, 217, 61, 0.18) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .wf-faq-accordion .accordion-item {
            border: 1px solid rgba(10, 37, 64, 0.06);
            border-radius: 16px !important;
            margin-bottom: 0.875rem;
            overflow: hidden;
            background: var(--bg-white);
            transition: all 0.3s;
            position: relative;
            box-shadow: 0 4px 12px rgba(10, 74, 107, 0.08);
        }
        .wf-faq-accordion .accordion-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--yellow) 0%, var(--primary) 100%);
            transform: scaleY(0);
            transform-origin: top;
            transition: transform 0.35s;
            z-index: 2;
        }
        .wf-faq-accordion .accordion-item:hover::before { transform: scaleY(1); }
        .wf-faq-accordion .accordion-item:has(.accordion-button:not(.collapsed))::before { transform: scaleY(1); }
        .wf-faq-accordion .accordion-item:hover {
            border-color: var(--light-blue);
            box-shadow: 0 12px 28px rgba(10, 74, 107, 0.15);
            transform: translateX(4px);
        }
        .wf-faq-accordion .accordion-button {
            background: var(--bg-white);
            color: var(--dark-blue);
            font-weight: 700;
            font-size: 1rem;
            padding: 1.375rem 1.5rem 1.375rem 1.875rem;
            border: none;
            box-shadow: none;
            letter-spacing: -0.015em;
            position: relative;
            display: flex;
            align-items: center;
        }
        .wf-faq-accordion .accordion-button::before {
            content: '';
            width: 10px;
            height: 10px;
            background: var(--yellow);
            border-radius: 50%;
            margin-right: 1rem;
            flex-shrink: 0;
            transition: all 0.3s;
            display: inline-block;
            box-shadow: 0 0 0 3px rgba(255, 217, 61, 0.2);
        }
        .wf-faq-accordion .accordion-button:not(.collapsed) {
            background: linear-gradient(180deg, var(--light-blue-pale) 0%, var(--bg-white) 100%);
            color: var(--primary);
        }
        .wf-faq-accordion .accordion-button:not(.collapsed)::before {
            background: var(--primary);
            box-shadow: 0 0 0 4px rgba(0, 90, 133, 0.2);
        }
        .wf-faq-accordion .accordion-button:focus { box-shadow: none; }
        .wf-faq-accordion .accordion-button::after { filter: invert(20%) sepia(40%) saturate(1200%) hue-rotate(180deg); opacity: 0.7; transition: transform 0.3s; }
        .wf-faq-accordion .accordion-body { padding: 0 1.5rem 1.5rem 1.875rem; font-size: 0.925rem; color: var(--text-secondary); line-height: 1.75; }
        .wf-faq-accordion .accordion-body strong { color: var(--dark-blue); font-weight: 700; }

        /* CTA */
        .wf-cta-wrap {
            background: linear-gradient(180deg, #F0F5F9 0%, #EAF2F8 100%);
        }
        .wf-cta {
            background: linear-gradient(135deg, #042640 0%, #063452 50%, #005A85 100%);
            border-radius: 28px;
            padding: 4.5rem 2.5rem;
            position: relative;
            overflow: hidden;
            text-align: center;
            box-shadow: 0 30px 60px rgba(4, 38, 64, 0.25);
        }
        .wf-cta::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 217, 61, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            animation: pulseGlow 6s ease-in-out infinite;
            pointer-events: none;
        }
        .wf-cta::after {
            content: '';
            position: absolute;
            bottom: -150px;
            left: -150px;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(168, 232, 249, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .wf-cta-content { position: relative; z-index: 2; }
        .wf-cta h2 { color: white; font-size: 2.25rem; font-weight: 800; letter-spacing: -0.035em; margin-bottom: 0.875rem; line-height: 1.2; }
        .wf-cta p { color: rgba(255, 255, 255, 0.85); font-size: 1.05rem; margin-bottom: 2.25rem; max-width: 540px; margin-left: auto; margin-right: auto; line-height: 1.7; }
        .wf-cta-actions { display: flex; gap: 0.875rem; justify-content: center; flex-wrap: wrap; }
        .wf-cta .wf-btn-hero-primary { background: var(--yellow); color: var(--dark-blue-deep); }
        .wf-cta .wf-btn-hero-primary:hover { background: #FFE066; color: var(--dark-blue-deep); }
        .wf-cta .wf-btn-hero-outline { border-color: rgba(255, 255, 255, 0.5); background: transparent; }
        .wf-cta .wf-btn-hero-outline:hover { background: white; color: var(--dark-blue-deep); border-color: white; }

        /* FOOTER */
        .wf-footer {
            background: linear-gradient(180deg, #042640 0%, #021a2d 100%);
            color: var(--light-blue);
            padding: 3.5rem 0 1.5rem;
        }
        .wf-footer-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 0.875rem; }
        .wf-footer-logo-mark { width: 40px; height: 40px; flex-shrink: 0; }
        .wf-footer-logo-mark svg { width: 100%; height: 100%; }
        .wf-footer-brand-name { color: white; font-weight: 800; font-size: 1.25rem; letter-spacing: -0.04em; }
        .wf-footer-brand-name .flow { color: var(--yellow); }
        .wf-footer-description { color: rgba(168, 232, 249, 0.7); font-size: 0.85rem; line-height: 1.7; max-width: 340px; margin-bottom: 0; }
        .wf-footer-heading { color: white; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 1rem; }
        .wf-footer-link { display: block; color: rgba(168, 232, 249, 0.7); font-size: 0.875rem; padding: 0.3rem 0; transition: all 0.2s; }
        .wf-footer-link:hover { color: var(--yellow); transform: translateX(4px); }
        .wf-footer-divider {
            border-top: 1px solid rgba(168, 232, 249, 0.12);
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .wf-footer-copy { font-size: 0.8rem; color: rgba(168, 232, 249, 0.6); margin: 0; }

        /* RESPONSIVE */
        @media (max-width: 991px) {
            .wf-hero h1 { font-size: 3rem; }
            .wf-hero .tagline { font-size: 1.4rem; }
            .wf-about-images { max-width: 100%; margin-bottom: 2rem; }
        }

        @media (max-width: 768px) {
            .wf-hero { padding: 4rem 0 3rem; min-height: auto; }
            .wf-hero h1 { font-size: 2.5rem; }
            .wf-hero .tagline { font-size: 1.2rem; }
            .wf-hero .sub-tagline { font-size: 1rem; }
            .wf-hero-buttons, .wf-cta-actions { flex-direction: column; }
            .wf-btn-hero-primary, .wf-btn-hero-outline { width: 100%; justify-content: center; }
            .wf-section { padding: 3.5rem 0; }
            .wf-section-title { font-size: 1.85rem; }
            .wf-cta { padding: 2.5rem 1.5rem; border-radius: 20px; }
            .wf-cta h2 { font-size: 1.6rem; }
            .wf-footer-divider { flex-direction: column; text-align: center; }
            .wf-pricing-item { flex-direction: column; align-items: flex-start; }
            .wf-logo-tagline { display: none; }
            .wf-about-images { grid-template-rows: 180px 180px; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="wf-navbar sticky-top">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <a href="homepage.php" class="wf-logo">
                    <div class="wf-logo-mark">
                        <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="wfLogoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#0076A8"/>
                                    <stop offset="100%" stop-color="#005A85"/>
                                </linearGradient>
                                <linearGradient id="wfWaveGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#FFD93D"/>
                                    <stop offset="100%" stop-color="#A8E8F9"/>
                                </linearGradient>
                            </defs>
                            <rect x="2" y="2" width="60" height="60" rx="16" fill="url(#wfLogoGrad)"/>
                            <path d="M14 24 L20 42 L26 30 L32 42 L38 24"
                                  stroke="url(#wfWaveGrad)" stroke-width="3.5"
                                  stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                            <circle cx="44" cy="24" r="2.5" fill="#FFD93D" opacity="0.9"/>
                            <circle cx="48" cy="32" r="1.8" fill="#FFD93D" opacity="0.7"/>
                            <circle cx="44" cy="40" r="1.2" fill="#FFD93D" opacity="0.5"/>
                            <path d="M14 48 Q22 44 32 48 T50 48"
                                  stroke="#A8E8F9" stroke-width="2"
                                  stroke-linecap="round" fill="none" opacity="0.6"/>
                        </svg>
                    </div>
                    <div class="wf-logo-text">
                        <span class="wf-logo-name">Wash<span class="flow">Flow</span></span>
                        <span class="wf-logo-tagline">Laundry System</span>
                    </div>
                </a>

                <div class="d-none d-lg-flex align-items-center gap-1" id="wfNavLinks">
                    <a href="#home" class="nav-link active" data-section="home">Home</a>
                    <a href="#about" class="nav-link" data-section="about">About Us</a>
                    <a href="#services" class="nav-link" data-section="services">Services</a>
                    <a href="#pricing" class="nav-link" data-section="pricing">Price</a>
                    <a href="#faq" class="nav-link" data-section="faq">FAQ</a>

                    <a href="login.php" class="wf-btn-nav-outline ms-3">Sign In</a>
                    <a href="register.php" class="wf-btn-nav-solid ms-2">Get Started</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- HERO -->
    <section class="wf-hero" id="home">
        <div class="wf-shape wf-shape-1"></div>
        <div class="wf-shape wf-shape-2"></div>

        <div class="container">
            <div class="wf-hero-content">
                <h1 class="anim-slide-left">
                    Laundry management,<br>
                    <span class="accent">made simple.</span>
                </h1>
                <p class="tagline anim-slide-left anim-delay-1">Manage. Track. Simplify.</p>
                <p class="sub-tagline anim-slide-left anim-delay-2">
                    A centralized platform for managing laundry bookings, customer orders,
                    payments, processing, inventory, and operational records.
                </p>
                <div class="wf-hero-buttons anim-slide-left anim-delay-3">
                    <a href="register.php" class="wf-btn-hero-primary">
                        Get Started <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="login.php" class="wf-btn-hero-outline">Sign In</a>
                </div>
            </div>
        </div>
    </section>

    <!-- ABOUT -->
    <section class="wf-section wf-about" id="about">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 anim-fade-up">
                    <div class="wf-about-images">
                        <div class="wf-about-image">
                            <img src="https://images.unsplash.com/photo-1604176354204-9268737828e4?w=600&h=800&fit=crop" alt="Folded towels">
                        </div>
                        <div class="wf-about-image">
                            <img src="https://images.unsplash.com/photo-1626806787461-102c1bfaaea1?w=600&h=400&fit=crop" alt="Laundry service">
                        </div>
                        <div class="wf-about-image">
                            <img src="https://images.unsplash.com/photo-1610557892470-55d9e80c0bce?w=600&h=400&fit=crop" alt="Washing machine">
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 anim-fade-up anim-delay-2">
                    <div class="wf-about-content">
                        <span class="wf-section-label">About the System</span>
                        <h2>What is WashFlow?</h2>
                        <p class="lead">
                            WashFlow is a web-based Laundry Management and Booking System designed
                            to manage laundry orders, customer bookings, payments, laundry processing,
                            inventory, scheduling, and operational records through a centralized platform.
                        </p>

                        <div class="wf-feature-list">
                            <div class="wf-feature-card">
                                <div class="wf-feature-card-icon"><i class="fas fa-soap"></i></div>
                                <div class="wf-feature-card-content">
                                    <h4>Personalized Experience</h4>
                                    <p>Your clothes deserve the best care. We meticulously sort garments—separating whites, colors, and darks—to preserve their vibrancy and longevity.</p>
                                </div>
                            </div>
                            <div class="wf-feature-card">
                                <div class="wf-feature-card-icon"><i class="fas fa-wind"></i></div>
                                <div class="wf-feature-card-content">
                                    <h4>Quality You Can Trust</h4>
                                    <p>Our professional drying process ensures garments are thoroughly dried at optimal temperatures, preserving fabric quality and preventing shrinkage.</p>
                                </div>
                            </div>
                            <div class="wf-feature-card">
                                <div class="wf-feature-card-icon"><i class="fas fa-tshirt"></i></div>
                                <div class="wf-feature-card-content">
                                    <h4>Convenience at Your Fingertips</h4>
                                    <p>Laundry day has never been easier. Every item is carefully folded and organized, ready to be stored in your closet.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SERVICES -->
    <section class="wf-section wf-services" id="services">
        <div class="container position-relative">
            <div class="wf-section-header anim-fade-up">
                <span class="wf-section-label">Services</span>
                <h2 class="wf-section-title">Laundry services supported by the system</h2>
                <p class="wf-section-subtitle">
                    The system is designed to handle the following service categories.
                </p>
            </div>

            <div class="row g-4 position-relative">
                <div class="col-md-6 col-lg-4 anim-fade-up anim-delay-1">
                    <div class="wf-service-card">
                        <div class="wf-service-image">
                            <img src="https://images.unsplash.com/photo-1604176354204-9268737828e4?w=600&h=400&fit=crop" alt="Wash & Fold">
                            <div class="wf-service-number">01</div>
                        </div>
                        <div class="wf-service-body">
                            <div class="wf-service-icon"><i class="fas fa-tshirt"></i></div>
                            <h3>Wash &amp; Fold</h3>
                            <p>Standard laundry processing for everyday clothing, including washing, drying, and folding.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4 anim-fade-up anim-delay-2">
                    <div class="wf-service-card">
                        <div class="wf-service-image">
                            <img src="https://images.unsplash.com/photo-1545173168-9f1947eebb7f?w=600&h=400&fit=crop" alt="Dry Cleaning">
                            <div class="wf-service-number">02</div>
                        </div>
                        <div class="wf-service-body">
                            <div class="wf-service-icon"><i class="fas fa-wind"></i></div>
                            <h3>Dry Cleaning</h3>
                            <p>Specialized cleaning for delicate garments and fabric types that require careful handling.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4 anim-fade-up anim-delay-3">
                    <div class="wf-service-card">
                        <div class="wf-service-image">
                            <img src="https://images.unsplash.com/photo-1626806787461-102c1bfaaea1?w=600&h=400&fit=crop" alt="Additional Services">
                            <div class="wf-service-number">03</div>
                        </div>
                        <div class="wf-service-body">
                            <div class="wf-service-icon"><i class="fas fa-plus"></i></div>
                            <h3>Additional Services</h3>
                            <p>Optional add-on services and special item processing available during booking.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PRICING -->
    <section class="wf-section wf-pricing" id="pricing">
        <div class="container">
            <div class="wf-section-header anim-fade-up">
                <span class="wf-section-label">Service Rates</span>
                <h2 class="wf-section-title">Service categories and rates</h2>
                <p class="wf-section-subtitle">
                    Pricing varies depending on the service type and order details.
                    Contact the administrator for specific pricing information.
                </p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-10 anim-fade-up anim-delay-1">
                    <div class="accordion wf-pricing-accordion" id="pricingAccordion">

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pricingBasic">
                                    <i class="fas fa-tshirt category-icon"></i> Basic Services
                                </button>
                            </h2>
                            <div id="pricingBasic" class="accordion-collapse collapse" data-bs-parent="#pricingAccordion">
                                <div class="accordion-body">
                                    <div class="wf-pricing-item">
                                        <div class="wf-pricing-item-info">
                                            <h5>Full Service</h5>
                                            <p>Wash, dry, and fold with standard detergent and fabric conditioner.</p>
                                        </div>
                                        <span class="wf-pricing-item-price">Varies</span>
                                    </div>
                                    <div class="wf-pricing-item">
                                        <div class="wf-pricing-item-info">
                                            <h5>Wash Only</h5>
                                            <p>Washing service for self-service customers.</p>
                                        </div>
                                        <span class="wf-pricing-item-price">Varies</span>
                                    </div>
                                    <div class="wf-pricing-item">
                                        <div class="wf-pricing-item-info">
                                            <h5>Dry Only</h5>
                                            <p>Drying service for pre-washed clothing.</p>
                                        </div>
                                        <span class="wf-pricing-item-price">Varies</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pricingSpecial">
                                    <i class="fas fa-bed category-icon"></i> Special Items
                                </button>
                            </h2>
                            <div id="pricingSpecial" class="accordion-collapse collapse" data-bs-parent="#pricingAccordion">
                                <div class="accordion-body">
                                    <div class="wf-pricing-item">
                                        <div class="wf-pricing-item-info">
                                            <h5>Blanket / Bedsheet</h5>
                                            <p>Heavy-duty cleaning for thick blankets and bedsheets.</p>
                                        </div>
                                        <span class="wf-pricing-item-price">Varies</span>
                                    </div>
                                    <div class="wf-pricing-item">
                                        <div class="wf-pricing-item-info">
                                            <h5>Comforter</h5>
                                            <p>Gentle care to maintain fluffiness and texture.</p>
                                        </div>
                                        <span class="wf-pricing-item-price">Varies</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pricingAddons">
                                    <i class="fas fa-plus-circle category-icon"></i> Add-On Services
                                </button>
                            </h2>
                            <div id="pricingAddons" class="accordion-collapse collapse" data-bs-parent="#pricingAccordion">
                                <div class="accordion-body">
                                    <div class="wf-pricing-item">
                                        <div class="wf-pricing-item-info">
                                            <h5>Extra Dry</h5>
                                            <p>Extended drying for thick fabrics and bulky items.</p>
                                        </div>
                                        <span class="wf-pricing-item-price">Varies</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="wf-section wf-faq" id="faq">
        <div class="container position-relative">
            <div class="wf-section-header anim-fade-up">
                <span class="wf-section-label">FAQ</span>
                <h2 class="wf-section-title">Frequently asked questions</h2>
                <p class="wf-section-subtitle">
                    Common questions about the WashFlow system.
                </p>
            </div>

            <div class="row justify-content-center position-relative">
                <div class="col-lg-9 anim-fade-up anim-delay-1">
                    <div class="accordion wf-faq-accordion" id="faqAccordion">

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    What is WashFlow?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    WashFlow is a web-based Laundry Management and Booking System designed to manage laundry orders, customer bookings, payments, laundry processing, inventory, scheduling, and operational records through a centralized platform.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Who can use the system?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    The system supports three user roles: <strong>Administrators</strong> (full system access), <strong>Staff</strong> (manage orders, bookings, payments, and inventory), and <strong>Customers</strong> (create bookings, track orders, submit payments, and provide feedback).
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    How does the booking process work?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Customers submit a booking through the platform. The booking is reviewed and approved by staff, converted into an order, and then processed through a defined workflow: <strong>Received → Processing → Ready → Completed</strong>. Customers can track the status of their order in real time.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    What payment methods are supported?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    The system supports multiple payment methods, including <strong>cash</strong> and <strong>GCash</strong>. For GCash payments, customers can upload proof of payment, which is then reviewed and verified by staff.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    Does the system manage inventory?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes. The system includes inventory management, allowing staff to track stock levels, monitor low-stock items, manage suppliers, and record purchase orders. Inventory can also be automatically deducted based on laundry orders.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                    Can customers submit feedback or complaints?
                                </button>
                            </h2>
                            <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes. Customers can submit feedback, complaints, or suggestions through the system. Staff can review, respond to, and mark these as resolved or closed.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                                    Is the system accessible on mobile devices?
                                </button>
                            </h2>
                            <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes. The system is designed with a responsive layout and can be accessed on desktop, tablet, and mobile devices through a standard web browser.
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA BANNER -->
    <section class="wf-section wf-cta-wrap" style="padding-top: 4rem; padding-bottom: 5rem;">
        <div class="container">
            <div class="wf-cta anim-fade-up">
                <div class="wf-cta-content">
                    <h2>Ready to streamline your laundry operations?</h2>
                    <p>
                        Create an account and start managing bookings, orders, payments,
                        and inventory through one centralized platform.
                    </p>
                    <div class="wf-cta-actions">
                        <a href="register.php" class="wf-btn-hero-primary">
                            Get Started <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="login.php" class="wf-btn-hero-outline">Sign In</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="wf-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5 col-md-12">
                    <div class="wf-footer-brand">
                        <div class="wf-footer-logo-mark">
                            <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="wfFooterLogoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#0076A8"/>
                                        <stop offset="100%" stop-color="#005A85"/>
                                    </linearGradient>
                                    <linearGradient id="wfFooterWaveGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" stop-color="#FFD93D"/>
                                        <stop offset="100%" stop-color="#A8E8F9"/>
                                    </linearGradient>
                                </defs>
                                <rect x="2" y="2" width="60" height="60" rx="16" fill="url(#wfFooterLogoGrad)"/>
                                <path d="M14 24 L20 42 L26 30 L32 42 L38 24"
                                      stroke="url(#wfFooterWaveGrad)" stroke-width="3.5"
                                      stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                <circle cx="44" cy="24" r="2.5" fill="#FFD93D" opacity="0.9"/>
                                <circle cx="48" cy="32" r="1.8" fill="#FFD93D" opacity="0.7"/>
                                <circle cx="44" cy="40" r="1.2" fill="#FFD93D" opacity="0.5"/>
                                <path d="M14 48 Q22 44 32 48 T50 48"
                                      stroke="#A8E8F9" stroke-width="2"
                                      stroke-linecap="round" fill="none" opacity="0.6"/>
                            </svg>
                        </div>
                        <span class="wf-footer-brand-name">Wash<span class="flow">Flow</span></span>
                    </div>
                    <p class="wf-footer-description">
                        A web-based Laundry Management and Booking System designed to manage
                        laundry orders, bookings, payments, and operational records through
                        a centralized platform.
                    </p>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="wf-footer-heading">System</div>
                    <a href="#about" class="wf-footer-link">About</a>
                    <a href="#services" class="wf-footer-link">Services</a>
                    <a href="#pricing" class="wf-footer-link">Pricing</a>
                    <a href="#faq" class="wf-footer-link">FAQ</a>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="wf-footer-heading">Account</div>
                    <a href="login.php" class="wf-footer-link">Sign In</a>
                    <a href="register.php" class="wf-footer-link">Register</a>
                </div>
                <div class="col-lg-3 col-md-4 col-12">
                    <div class="wf-footer-heading">System Info</div>
                    <p class="wf-footer-description" style="font-size: 0.8rem;">
                        Laundry Management and Booking System<br>
                        Version 2.0 — WashFlow
                    </p>
                </div>
            </div>

            <div class="wf-footer-divider">
                <p class="wf-footer-copy">
                    &copy; <?php echo $currentYear; ?> WashFlow. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const animatedElements = document.querySelectorAll('.anim-fade-up, .anim-slide-left');

            animatedElements.forEach(el => { el.style.opacity = '0'; });

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '';
                        entry.target.style.animationPlayState = 'running';
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            animatedElements.forEach(el => {
                el.style.animationPlayState = 'paused';
                observer.observe(el);
            });

            const heroElements = document.querySelectorAll('.wf-hero .anim-slide-left');
            heroElements.forEach(el => {
                el.style.animationPlayState = 'running';
                el.style.opacity = '';
            });

            const sections = document.querySelectorAll('section[id]');
            const navLinks = document.querySelectorAll('.wf-navbar .nav-link[data-section]');

            function updateActiveNav() {
                let current = 'home';
                const scrollPos = window.scrollY + 150;

                sections.forEach(section => {
                    const top = section.offsetTop;
                    const height = section.offsetHeight;
                    if (scrollPos >= top && scrollPos < top + height) {
                        current = section.getAttribute('id');
                    }
                });

                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.dataset.section === current) {
                        link.classList.add('active');
                    }
                });
            }

            window.addEventListener('scroll', updateActiveNav, { passive: true });
            updateActiveNav();
        });
    </script>
</body>
</html>