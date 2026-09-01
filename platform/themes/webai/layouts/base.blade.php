<!doctype html>
<html {!! Theme::htmlAttributes() !!}>
    <head>
        <meta charset="UTF-8">
        <meta content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=5, user-scalable=1" name="viewport">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ Theme::getSiteTitle() }}</title>

        {!! Theme::header() !!}

        <style>
            :root {
                color-scheme: light;
                --webai-bg: #f7f7fb;
                --webai-panel: rgba(255, 255, 255, 0.76);
                --webai-panel-soft: rgba(255, 255, 255, 0.64);
                --webai-border: rgba(214, 214, 224, 0.9);
                --webai-border-strong: #ff7a00;
                --webai-text: #424252;
                --webai-muted: #5c5c68;
                --webai-dim: #777783;
                --webai-accent: #ff7a00;
                --webai-accent-soft: #fff1e0;
                --webai-shadow: 0 18px 48px rgba(42, 42, 60, 0.14);
            }

            * {
                box-sizing: border-box;
            }

            html,
            body {
                min-height: 100%;
                margin: 0;
                background: var(--webai-bg);
                color: var(--webai-text);
                font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            }

            body {
                overflow-x: hidden;
            }

            button,
            textarea,
            select {
                font: inherit;
            }

            .webai-shell {
                position: relative;
                min-height: 100vh;
                display: grid;
                grid-template-columns: 232px minmax(0, 1fr);
                background: #fff;
            }

            .webai-mobile-header {
                display: none;
            }

            .webai-sidebar {
                position: sticky;
                top: 0;
                height: 100vh;
                display: flex;
                flex-direction: column;
                padding: 20px 14px 18px;
                border-right: 1px solid rgba(198, 198, 210, 0.55);
                background: #fff;
                box-shadow: none;
            }

            .webai-brand {
                display: flex;
                align-items: center;
                justify-content: flex-start;
                gap: 10px;
                padding: 0 10px 26px;
                color: var(--webai-text);
                text-decoration: none;
            }

            .webai-brand-mark {
                width: 48px;
                height: 48px;
                display: grid;
                place-items: center;
                border-radius: 10px;
                background: rgba(255, 255, 255, 0.68);
                color: #d6d6de;
                font-weight: 900;
                font-size: 28px;
                box-shadow: 0 10px 26px rgba(95, 95, 115, 0.16);
            }

            .webai-brand-text {
                font-size: 25px;
                font-weight: 800;
                letter-spacing: 0;
            }

            .webai-brand-text span {
                color: var(--webai-text);
            }

            .webai-brand-logo {
                width: auto;
                max-width: 100%;
                height: 44px;
                max-height: 44px;
                object-fit: contain;
            }

            .webai-nav {
                display: grid;
                gap: 0;
            }

            .webai-sidebar-navigation {
                min-height: 0;
                overflow-y: auto;
                padding: 0 2px 12px;
            }

            .webai-nav-list,
            .webai-nav-sub {
                display: grid;
                gap: 4px;
                margin: 0;
                padding: 0;
                list-style: none;
            }

            .webai-nav-sub {
                gap: 6px;
                margin: 6px 0 4px 34px;
            }

            .webai-nav-item {
                display: flex;
                align-items: center;
                gap: 12px;
                min-height: 42px;
                padding: 0 12px;
                border: 1px solid transparent;
                border-radius: 8px;
                background: transparent;
                color: var(--webai-muted);
                text-decoration: none;
                font-size: 14px;
                font-weight: 500;
                box-shadow: none;
            }

            .webai-nav-sub .webai-nav-item {
                min-height: 38px;
                padding: 0 12px;
                font-size: 14px;
                color: var(--webai-dim);
            }

            .webai-nav-item:hover {
                border-color: var(--webai-accent);
                color: var(--webai-text);
                background: rgba(255, 251, 246, 0.92);
                box-shadow: none;
            }

            .webai-nav-item.is-active {
                border-color: var(--webai-border-strong);
                background: rgba(255, 247, 237, 0.95);
                color: var(--webai-accent);
                box-shadow: none;
            }

            .webai-nav-icon {
                width: 18px;
                height: 18px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: currentColor;
                text-align: center;
            }

            .webai-nav-section-label {
                margin: 20px 0 6px;
                padding: 0 12px;
                color: var(--webai-dim);
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 0.04em;
                line-height: 1.4;
                list-style: none;
                text-transform: uppercase;
            }

            .webai-sidebar-promo {
                margin-top: auto;
                padding: 14px;
                border: 1px solid var(--webai-border-strong);
                border-radius: 10px;
                background: rgba(255, 247, 237, 0.95);
                color: var(--webai-muted);
            }

            .webai-sidebar-promo__icon {
                display: inline-flex;
                margin-bottom: 7px;
                color: var(--webai-accent);
                font-size: 18px;
            }

            .webai-sidebar-promo__title {
                color: var(--webai-accent);
                font-size: 13px;
                font-weight: 700;
            }

            .webai-sidebar-promo p {
                margin: 8px 0 12px;
                font-size: 12px;
                line-height: 1.45;
            }

            .webai-sidebar-promo a {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                min-height: 36px;
                border-radius: 7px;
                background: var(--webai-accent);
                color: #fff;
                font-size: 12px;
                font-weight: 700;
                text-decoration: none;
            }

            .webai-nav-icon svg,
            .webai-tool-icon svg {
                width: 18px;
                height: 18px;
                fill: none;
                stroke: currentColor;
                stroke-width: 2;
            }

            .webai-nav-chevron {
                margin-left: auto;
                color: currentColor;
                font-size: 16px;
            }

            .webai-nav-dot {
                width: 8px;
                height: 8px;
                border: 1px solid currentColor;
                border-radius: 3px;
            }

            .webai-user {
                margin-top: 16px;
                display: grid;
                grid-template-columns: 38px minmax(0, 1fr);
                gap: 9px;
                align-items: center;
                padding: 0 6px;
                position: relative;
                cursor: pointer;
            }

            .webai-avatar {
                width: 34px;
                height: 34px;
                display: grid;
                place-items: center;
                border: 1px solid rgba(220, 220, 230, 0.95);
                border-radius: 50%;
                color: #c8c8d2;
                background: rgba(255, 255, 255, 0.72);
                box-shadow: 0 8px 20px rgba(80, 80, 100, 0.12);
            }

            .webai-credit {
                color: var(--webai-text);
                font-size: 13px;
                font-weight: 600;
            }

            .webai-email {
                margin-top: 3px;
                color: var(--webai-muted);
                font-size: 11px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .webai-logout {
                position: absolute;
                right: 6px;
                bottom: calc(100% + 12px);
                left: 6px;
                min-height: 38px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 1px solid var(--webai-accent);
                border-radius: 9px;
                background: var(--webai-accent);
                color: #fff;
                font-size: 13px;
                font-weight: 850;
                text-decoration: none;
                opacity: 0;
                pointer-events: none;
                transform: translateY(6px);
                box-shadow: 0 4px 12px rgba(255, 122, 0, 0.2);
                transition: opacity 0.18s ease, transform 0.18s ease, background 0.18s ease;
            }

            .webai-user.is-logout-open .webai-logout {
                opacity: 1;
                pointer-events: auto;
                transform: translateY(0);
            }

            .webai-logout:hover {
                background: #e86e00;
            }

            .webai-event-modal[hidden] {
                display: none;
            }

            .webai-event-modal {
                position: fixed;
                z-index: 1100;
                inset: 0;
                display: grid;
                place-items: center;
                padding: 24px;
            }

            .webai-event-modal__backdrop {
                position: absolute;
                inset: 0;
                background: rgba(22, 24, 36, 0.58);
                backdrop-filter: blur(5px);
            }

            .webai-event-modal__dialog {
                position: relative;
                z-index: 1;
                width: min(760px, 100%);
                min-height: 420px;
                display: grid;
                grid-template-columns: 44% 56%;
                overflow: hidden;
                border-radius: 18px;
                background: #fff;
                box-shadow: 0 28px 80px rgba(0, 0, 0, 0.32);
            }

            .webai-event-modal__image img {
                width: 100%;
                height: 100%;
                display: block;
                object-fit: cover;
            }

            .webai-event-modal__content {
                align-self: center;
                padding: 52px 46px;
            }

            .webai-event-modal__eyebrow {
                display: block;
                margin-bottom: 12px;
                color: var(--webai-accent);
                font-size: 13px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.08em;
            }

            .webai-event-modal__content h2 {
                margin: 0;
                color: #222431;
                font-size: clamp(28px, 4vw, 38px);
                line-height: 1.1;
            }

            .webai-event-modal__content p {
                margin: 18px 0;
                color: #6e7080;
                font-size: 15px;
                line-height: 1.65;
            }

            .webai-event-modal__meta {
                display: grid;
                gap: 8px;
                margin-bottom: 26px;
                color: #4c4f5d;
                font-size: 13px;
                font-weight: 650;
            }

            .webai-event-modal__action {
                min-height: 46px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0 24px;
                border-radius: 10px;
                background: var(--webai-accent);
                color: #fff;
                font-size: 14px;
                font-weight: 850;
                text-decoration: none;
            }

            .webai-event-modal__close {
                position: absolute;
                z-index: 2;
                top: 13px;
                right: 15px;
                width: 34px;
                height: 34px;
                border: 0;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.84);
                color: #666978;
                font-size: 28px;
                font-weight: 300;
                line-height: 1;
                cursor: pointer;
            }

            .webai-auth-actions {
                margin-top: auto;
                display: grid;
                grid-template-columns: 1fr;
                gap: 8px;
                padding: 0 6px;
            }

            .webai-auth-action {
                min-height: 38px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 1px solid var(--webai-accent);
                border-radius: 9px;
                text-decoration: none;
                font-size: 14px;
                font-weight: 850;
                box-shadow: 0 4px 12px rgba(255, 122, 0, 0.12);
                transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
            }

            .webai-auth-action--login {
                background: rgba(255, 255, 255, 0.82);
                color: var(--webai-accent);
            }

            .webai-auth-action--register {
                background: var(--webai-accent);
                color: #ffffff;
            }

            .webai-auth-action:hover {
                box-shadow: 0 8px 18px rgba(255, 122, 0, 0.18);
            }

            .webai-auth-action--login:hover {
                background: var(--webai-accent-soft);
                color: var(--webai-accent);
            }

            .webai-auth-action--register:hover {
                background: #d46400;
                color: #ffffff;
            }

            .webai-main {
                min-width: 0;
                padding: 28px 34px 20px;
                background: #fff;
            }

            .webai-page-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 24px;
                width: min(1280px, 100%);
                min-height: 62px;
                margin: 0 auto 26px;
                padding-bottom: 18px;
                border-bottom: 1px solid rgba(198, 198, 210, 0.5);
            }

            .webai-page-header__intro h1 {
                margin: 0;
                color: var(--webai-text);
                font-size: 21px;
                font-weight: 800;
                line-height: 1.25;
            }

            .webai-page-header__intro p {
                margin: 6px 0 0;
                color: var(--webai-muted);
                font-size: 13px;
            }

            .webai-page-header__actions {
                display: flex;
                align-items: center;
                gap: 14px;
                flex-shrink: 0;
            }

            .webai-page-header__balance {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                min-height: 38px;
                padding: 0 12px;
                border: 1px solid rgba(198, 198, 210, 0.5);
                border-radius: 9px;
                color: var(--webai-muted);
                font-size: 12px;
                background: transparent;
            }

            .webai-page-header__balance svg {
                width: 17px;
                height: 17px;
                color: var(--webai-accent);
                fill: none;
                stroke: currentColor;
                stroke-width: 2;
            }

            .webai-page-header__icon-button,
            .webai-page-header__profile {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 0;
                padding: 0;
                color: var(--webai-muted);
                background: transparent;
                cursor: pointer;
            }

            .webai-page-header__icon-button svg {
                width: 21px;
                height: 21px;
                fill: none;
                stroke: currentColor;
                stroke-width: 1.8;
                stroke-linecap: round;
                stroke-linejoin: round;
            }

            .webai-page-header__profile {
                gap: 8px;
                font-size: 12px;
                font-weight: 600;
                white-space: nowrap;
            }

            .webai-page-header__profile > svg {
                width: 15px;
                height: 15px;
                fill: none;
                stroke: currentColor;
                stroke-width: 2;
            }

            .webai-page-header__avatar {
                width: 30px;
                height: 30px;
                display: inline-grid;
                place-items: center;
                border-radius: 50%;
                color: #fff;
                background: var(--webai-accent);
                font-size: 13px;
                font-weight: 800;
            }

            .webai-page-header__avatar img {
                width: 100%;
                height: 100%;
                border-radius: inherit;
                object-fit: cover;
            }

            .webai-content {
                width: min(1280px, 100%);
                margin: 0 auto;
            }

            .motion-workspace { color: var(--webai-text); font-size: 13px; }
            .motion-section-heading, .motion-card__heading { display: flex; align-items: center; gap: 8px; }
            .motion-section-heading { margin-bottom: 14px; }
            .motion-section-heading h2, .motion-card__heading h2 { margin: 0; font-size: 16px; font-weight: 800; }
            .motion-section-heading span, .motion-card__heading > span { color: var(--webai-accent); font-size: 12px; }
            .motion-models__carousel { position: relative; } .motion-models__viewport { overflow: hidden; } .motion-models__grid { display: flex; gap: 12px; transition: transform .25s ease; }
            .motion-model { flex: 0 0 calc((100% - 48px) / 5); overflow: hidden; padding: 0; border: 1px solid rgba(198,198,210,.55); border-radius: 11px; color: inherit; background: transparent; text-align: left; cursor: pointer; }
            .motion-models__arrow { position: absolute; z-index: 2; top: 50%; width: 38px; height: 38px; display: grid; place-items: center; border: 1px solid rgba(198,198,210,.65); border-radius: 50%; color: var(--webai-text); background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,.1); font-size: 30px; line-height: 1; cursor: pointer; transform: translateY(-50%); } .motion-models__arrow[hidden] { display: none; } .motion-models__arrow:disabled { opacity: .4; cursor: default; } .motion-models__arrow--previous { left: -19px; } .motion-models__arrow--next { right: -19px; }
            .motion-model.is-selected { border-color: var(--webai-accent); box-shadow: 0 0 0 1px var(--webai-accent); }
            .motion-model__image { position: relative; height: 145px; overflow: hidden; background: #ddd; }
            .motion-model__image > img { width: 100%; height: 100%; display: block; object-fit: cover; }
            .motion-model__tag { position: absolute; top: 8px; left: 8px; padding: 4px 7px; border-radius: 4px; color: #fff; background: var(--webai-accent); font-size: 10px; font-weight: 700; }
            .motion-model > strong { display: block; padding: 11px 12px 0; font-size: 14px; } .motion-model > strong em { float: right; color: var(--webai-accent); font-style: normal; }
            .motion-model > p { min-height: 54px; margin: 7px 12px; overflow: hidden; color: var(--webai-muted); font-size: 12px; line-height: 1.42; overflow-wrap: anywhere; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3; }
            .motion-model footer { display: flex; justify-content: flex-end; padding: 9px 12px; border-top: 1px solid rgba(198,198,210,.35); color: var(--webai-muted); font-size: 12px; } .motion-model footer b { color: var(--webai-accent); }
            .motion-workspace__columns { display: grid; grid-template-columns: 1.45fr .85fr 1.1fr; gap: 20px; margin-top: 28px; }
            .motion-card { min-width: 0; padding: 0; } .motion-card__heading { margin-bottom: 8px; } .motion-card__heading button { margin-left: auto; padding: 6px 12px; border: 0; border-radius: 5px; color: #fff; background: var(--webai-accent); font-size: 11px; }
            .motion-note { margin: 0 0 9px; color: var(--webai-muted); font-size: 11px; }
            .motion-table { border: 1px solid rgba(198,198,210,.5); border-radius: 8px; overflow: hidden; }
            .motion-table.has-extended-rows { max-height: 365px; overflow-y: auto; }
            .motion-table__header, .motion-table__row { display: grid; grid-template-columns: .4fr 1.25fr 1.35fr .35fr; align-items: center; gap: 5px; padding: 7px 9px; } .motion-table__header { position: sticky; top: 0; z-index: 3; color: var(--webai-muted); background: #fff; font-size: 10px; } .motion-table__row { min-height: 65px; border-top: 1px solid rgba(198,198,210,.35); }
            .motion-row-number { color: var(--webai-muted); font-size: 11px; } .motion-upload { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 51px; height: 51px; overflow: hidden; border: 1px dashed #b7becb; border-radius: 5px; color: var(--webai-muted); background: #fff; cursor: pointer; transition: border-color .2s, color .2s, background .2s; } .motion-upload:hover, .motion-upload:focus-within { border-color: var(--webai-accent); color: var(--webai-accent); background: rgba(255,119,0,.05); } .motion-upload.has-file { border-style: solid; border-color: var(--webai-accent); } .motion-upload input { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); clip-path: inset(50%); white-space: nowrap; } .motion-upload__add { position: relative; z-index: 1; font-size: 26px; font-weight: 400; line-height: 1; } .motion-upload__preview { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; pointer-events: none; } .motion-delete { border: 0; color: #d84b60; background: transparent; cursor: pointer; }
            .motion-tip { margin: 10px 0; color: var(--webai-muted); font-size: 11px; }
            .motion-selects { display: grid; grid-template-columns: repeat(2, 1fr); gap: 7px; margin: 14px 0; } .motion-selects select { width: 100%; margin-top: 5px; padding: 6px; border: 1px solid rgba(198,198,210,.5); border-radius: 5px; color: inherit; background: transparent; font-size: 10px; }
            .motion-schedule { margin-top: 17px; } .motion-schedule > input { position: absolute; opacity: 0; } .motion-schedule__toggle { display: flex !important; align-items: center; justify-content: space-between; color: var(--webai-text) !important; font-size: 12px !important; cursor: pointer; } .motion-schedule__toggle i { width: 32px; height: 18px; border-radius: 10px; background: rgba(198,198,210,.8); transition: background .2s ease; } .motion-schedule__toggle i::after { width: 14px; height: 14px; display: block; margin: 2px; border-radius: 50%; background: #fff; content: ''; transition: transform .2s ease; } .motion-schedule__options { display: none; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 12px; } .motion-schedule__options label { color: var(--webai-muted); font-size: 11px; } .motion-schedule__options input, .motion-schedule__options select { width: 100%; margin-top: 5px; padding: 6px; border: 1px solid rgba(198,198,210,.5); border-radius: 5px; color: inherit; background: transparent; font: inherit; box-sizing: border-box; } .motion-schedule > input:checked + .motion-schedule__toggle i { background: var(--webai-accent); } .motion-schedule > input:checked + .motion-schedule__toggle i::after { transform: translateX(14px); } .motion-schedule > input:checked ~ .motion-schedule__options { display: grid; } .motion-preview dd:nth-child(3) { color: var(--webai-accent); }
            .motion-preview dl { margin: 20px 0; padding: 11px 13px; border: 1px solid rgba(198,198,210,.45); border-radius: 8px; } .motion-preview dl div { display: flex; justify-content: space-between; gap: 10px; padding: 6px 0; font-size: 11px; } .motion-preview dt { color: var(--webai-muted); } .motion-preview dd { margin: 0; font-weight: 700; } .motion-preview__description { display: block !important; } .motion-preview__description dt { margin-bottom: 5px; } .motion-preview__description dd { color: var(--webai-text); font-weight: 400; line-height: 1.45; overflow-wrap: anywhere; white-space: pre-wrap; } .motion-create { width: 100%; min-height: 38px; border: 0; border-radius: 7px; color: #fff; background: var(--webai-accent); font-size: 13px; font-weight: 700; cursor: pointer; }
            @media (max-width: 1100px) { .motion-workspace__columns { grid-template-columns: 1fr 1fr; } .motion-preview { grid-column: 1 / -1; } } @media (max-width: 640px) { .motion-model { flex-basis: calc((100% - 12px) / 2); } .motion-models__arrow--previous { left: -12px; } .motion-models__arrow--next { right: -12px; } .motion-workspace__columns { grid-template-columns: 1fr; } .motion-preview { grid-column: auto; } }

            .my-videos-dashboard { min-height: calc(100vh - 180px); color: var(--webai-text); }
            .my-videos-dashboard__top { display: flex; justify-content: space-between; gap: 32px; }
            .my-videos-dashboard h1 { margin: 0; font-size: 26px; font-weight: 800; }
            .my-videos-dashboard h1 span { color: var(--webai-accent); }
            .my-videos-dashboard p { margin: 8px 0 18px; color: var(--webai-muted); font-size: 14px; }
            .my-videos-dashboard__note { color: var(--webai-muted); font-size: 13px; font-weight: 600; line-height: 1.45; }
            .my-videos-dashboard__note strong { color: var(--webai-text); }
            .my-videos-dashboard__controls { width: min(640px, 52%); display: grid; align-content: start; gap: 12px; }
            .my-videos-search { display: block; margin-left: auto; width: min(350px, 100%); }
            .my-videos-search input { width: 100%; height: 48px; padding: 0 16px; border: 1px solid rgba(198,198,210,.65); border-radius: 12px; color: var(--webai-text); background: #fff; font: inherit; box-sizing: border-box; }
            .my-videos-search input:focus { border-color: var(--webai-accent); outline: 0; }
            .my-videos-filters { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: 9px; }
            .my-videos-filters button { min-height: 34px; padding: 0 13px; border: 1px solid rgba(198,198,210,.7); border-radius: 18px; color: var(--webai-muted); background: #fff; font: inherit; font-size: 12px; cursor: pointer; }
            .my-videos-filters button.is-active, .my-videos-filters button:hover { border-color: var(--webai-accent); color: var(--webai-accent); }
            .my-videos-dashboard__meta { display: flex; justify-content: space-between; margin-top: 21px; color: var(--webai-muted); font-size: 13px; }
            .my-videos-empty { display: flex; flex-direction: column; align-items: flex-start; gap: 4px; margin-top: 66px; padding: 24px; border: 1px solid rgba(198,198,210,.65); border-radius: 18px; background: #fff; }
            .my-videos-empty strong { font-size: 14px; } .my-videos-empty strong::first-letter { color: var(--webai-accent); }
            .my-videos-empty span { color: var(--webai-muted); font-size: 13px; }
            .my-videos-empty a { margin-top: 9px; color: var(--webai-accent); font-size: 13px; font-weight: 700; text-decoration: none; }
            @media (max-width: 760px) { .my-videos-dashboard__top { flex-direction: column; } .my-videos-dashboard__controls { width: 100%; } .my-videos-search { margin-left: 0; } .my-videos-filters { justify-content: flex-start; } .my-videos-empty { margin-top: 36px; } }

            .service-plans { width: min(1100px, 100%); margin: 0 auto; color: var(--webai-text); }
            .service-plans__header { text-align: center; } .service-plans__header h1 { margin: 0; font-size: clamp(28px, 4vw, 42px); font-weight: 850; } .service-plans__header h1 span { color: var(--webai-accent); } .service-plans__header > p { margin: 10px 0 26px; color: var(--webai-muted); font-size: 15px; }
            .service-plans__tabs { display: inline-flex; gap: 5px; padding: 5px; border: 1px solid rgba(198,198,210,.6); border-radius: 24px; background: #fff; } .service-plans__tabs button { border: 0; border-radius: 18px; padding: 9px 15px; color: var(--webai-muted); background: transparent; font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; } .service-plans__tabs button.is-active { color: #fff; background: var(--webai-text); } .service-plans__tabs em { margin-left: 4px; padding: 3px 6px; border-radius: 8px; color: #fff; background: #ef5350; font-size: 9px; font-style: normal; }
            .service-plans__grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; align-items: stretch; margin-top: 44px; }
            .service-plan { position: relative; display: flex; flex-direction: column; min-height: 520px; padding: 27px; border: 1px solid rgba(198,198,210,.65); border-radius: 16px; background: #fff; } .service-plan.is-featured { border: 2px solid var(--webai-accent); box-shadow: 0 8px 25px rgba(255,122,0,.12); } .service-plan__badge { position: absolute; top: -17px; left: 50%; padding: 8px 16px; border-radius: 18px; color: #fff; background: var(--webai-accent); font-size: 11px; font-weight: 800; white-space: nowrap; transform: translateX(-50%); }
            .service-plan header { display: flex; align-items: flex-start; gap: 12px; } .service-plan header i { width: 37px; height: 37px; display: grid; place-items: center; border-radius: 10px; color: var(--webai-accent); background: rgba(255,122,0,.1); font-size: 20px; font-style: normal; } .service-plan h2 { margin: 0; font-size: 19px; } .service-plan header p { margin: 4px 0 0; color: var(--webai-muted); font-size: 11px; line-height: 1.35; }
            .service-plan__price { margin: 25px 0 28px; color: var(--webai-text); font-size: 30px; font-weight: 850; } .service-plan.is-featured .service-plan__price { color: var(--webai-accent); } .service-plan__price small { color: var(--webai-muted); font-size: 13px; font-weight: 500; }
            .service-plan dl { margin: 0 0 23px; } .service-plan dl div { display: flex; justify-content: space-between; padding: 4px 0; font-size: 12px; } .service-plan dt { color: var(--webai-muted); } .service-plan dd { margin: 0; font-weight: 700; }
            .service-plan__button { width: 100%; min-height: 46px; border: 1px solid rgba(198,198,210,.7); border-radius: 9px; color: var(--webai-text); background: #fff; font: inherit; font-size: 14px; font-weight: 800; cursor: pointer; transition: background-color .18s ease, border-color .18s ease, color .18s ease; } .service-plan__button:hover, .service-plan__button:focus-visible, .service-plan.is-featured .service-plan__button { border-color: var(--webai-accent); color: #fff; background: var(--webai-accent); }
            .service-plan__saving { margin: 10px 0 5px; padding: 9px; border: 1px solid rgba(255,122,0,.4); border-radius: 8px; color: var(--webai-accent); text-align: center; font-size: 11px; font-weight: 700; }
            .service-plan ul { display: grid; gap: 10px; margin: 22px 0; padding: 0; list-style: none; color: #358259; font-size: 12px; } .service-plan li span { color: var(--webai-muted); } .service-plan footer { margin-top: auto; padding-top: 14px; border-top: 1px solid rgba(198,198,210,.35); color: var(--webai-muted); font-size: 10px; line-height: 1.7; }
            @media (max-width: 900px) { .service-plans__grid { grid-template-columns: 1fr; max-width: 460px; margin-right: auto; margin-left: auto; } .service-plan__button { border-color: var(--webai-accent); color: #fff; background: var(--webai-accent); } }

            .webai-tool-page {
                min-height: calc(100vh - 48px);
                display: flex;
                flex-direction: column;
                width: min(1220px, 100%);
                margin: 0 auto;
                padding-bottom: 18px;
            }

            .webai-title {
                margin: 0;
                font-size: clamp(34px, 4vw, 44px);
                line-height: 1.1;
                font-weight: 900;
                letter-spacing: 0;
                color: #545463;
                text-shadow: 0 2px 4px rgba(80, 80, 100, 0.14);
            }

            .webai-subtitle {
                max-width: 620px;
                margin: 8px 0 26px;
                color: #2f2f3a;
                font-size: 17px;
                line-height: 1.35;
                font-weight: 500;
            }

            .webai-section-title {
                display: flex;
                align-items: center;
                gap: 10px;
                margin: 0 0 34px;
                font-size: 21px;
                font-weight: 850;
                color: #545463;
            }

            .webai-section-title span {
                color: #6a6a76;
            }

            .webai-empty {
                min-height: 132px;
                display: grid;
                gap: 16px;
                place-items: center;
                margin-bottom: 40px;
                padding: 18px;
                border: 1px solid rgba(236, 236, 244, 0.96);
                border-radius: 8px;
                background: rgba(255, 255, 255, 0.72);
                color: #2f2f3a;
                font-size: 16px;
                font-weight: 400;
                box-shadow: var(--webai-shadow);
            }

            .webai-empty.has-items {
                place-items: stretch;
            }

            .webai-empty.has-items [data-webai-recent-empty] {
                display: none;
            }

            .webai-recent-list {
                width: 100%;
                display: flex;
                gap: 14px;
                overflow-x: auto;
                overflow-y: hidden;
                padding: 2px 2px 8px;
                scroll-snap-type: x proximity;
            }

            .webai-recent-list:empty {
                display: none;
            }

            .webai-generation-panel {
                margin-bottom: 34px;
            }

            .webai-generation-panel[hidden] {
                display: none !important;
            }

            .webai-generation-list {
                display: grid;
                gap: 18px;
                justify-items: center;
            }

            .webai-generation-card {
                width: min(720px, 100%);
                min-height: 0;
                position: relative;
                padding: 28px 24px;
                border: 1px solid rgba(236, 236, 244, 0.96);
                border-radius: 28px;
                background:
                    radial-gradient(circle, rgba(92, 92, 104, 0.28) 1px, transparent 1.5px) 0 0 / 22px 22px,
                    linear-gradient(90deg, rgba(255, 255, 255, 0.52), rgba(255, 255, 255, 0.96) 70%),
                    rgba(255, 255, 255, 0.72);
                color: var(--webai-text);
                box-shadow: var(--webai-shadow);
            }

            .webai-generation-card.is-loading {
                animation: webai-dots-drift 1.8s linear infinite;
            }

            @keyframes webai-dots-drift {
                from {
                    background-position:
                        0 0,
                        0 0,
                        0 0;
                }

                to {
                    background-position:
                        44px 0,
                        0 0,
                        0 0;
                }
            }

            .webai-recent-item {
                flex: 0 0 92px;
                aspect-ratio: 1;
                overflow: hidden;
                border: 1px solid rgba(236, 236, 244, 0.96);
                border-radius: 6px;
                background: rgba(255, 255, 255, 0.8);
                box-shadow: 0 8px 18px rgba(42, 42, 60, 0.08);
                cursor: pointer;
                scroll-snap-align: start;
            }

            .webai-recent-item:hover {
                border-color: rgba(255, 122, 0, 0.72);
                box-shadow: 0 10px 22px rgba(255, 122, 0, 0.14);
            }

            .webai-recent-item video,
            .webai-recent-item img {
                width: 100%;
                height: 100%;
                display: block;
                object-fit: cover;
            }

            .webai-generation-card.is-completed {
                background: rgba(255, 255, 255, 0.78);
            }

            .webai-generation-card.is-failed {
                background: rgba(255, 247, 247, 0.84);
                border-color: rgba(255, 122, 122, 0.48);
            }

            .webai-generation-status {
                min-height: 32px;
                margin: 0 112px 18px 0;
                color: #5c5c68;
                font-size: 16px;
                font-weight: 800;
            }

            .webai-generation-download {
                position: absolute;
                z-index: 5;
                top: 38px;
                right: 6px;
                width: 26px;
                min-height: 26px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0;
                border: 0;
                border-radius: 50%;
                background: rgba(26, 27, 35, 0.76);
                color: #fff;
                font-size: 0;
                font-weight: 800;
                line-height: 1;
                text-decoration: none;
                opacity: 0;
                transform: scale(0.86);
                transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
            }

            .webai-generation-download::after {
                content: '\2193';
                font-size: 18px;
                font-weight: 800;
            }

            .webai-generation-download:hover {
                background: var(--webai-accent);
                color: #fff;
                transform: translateY(-1px);
            }

            .webai-generation-media {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 12px;
                flex-wrap: wrap;
            }

            .webai-generation-media video,
            .webai-generation-media img {
                width: auto;
                max-width: 100%;
                max-height: min(68vh, 620px);
                display: block;
                border-radius: 16px;
                background: #000;
                object-fit: contain;
            }

            .webai-generation-media video {
                min-width: min(100%, 360px);
            }

            /* The video lab is one gallery: newest generated media is prepended to this grid. */
            .webai-video-tool-page > .webai-title,
            .webai-video-tool-page > .webai-subtitle,
            .webai-video-tool-page > .webai-section-title,
            .webai-video-tool-page > .webai-generation-panel {
                display: none;
            }

            .webai-video-tool-page > .webai-empty {
                min-height: 0;
                display: block;
                margin: 0 0 40px;
                padding: 0;
                border: 0;
                border-radius: 0;
                background: transparent;
                box-shadow: none;
            }

            .webai-video-tool-page > .webai-empty [data-webai-recent-empty] {
                margin: 0;
            }

            .webai-video-tool-page > .webai-empty.has-items [data-webai-recent-empty] {
                display: none;
            }

            .webai-video-load-more {
                width: 100%;
                height: 1px;
            }

            .webai-video-tool-page .webai-recent-list {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                grid-auto-flow: dense;
                grid-auto-rows: 160px;
                gap: 18px;
                overflow: visible;
                padding: 0;
            }

            .webai-video-tool-page .webai-recent-item,
            .webai-video-tool-page .webai-generation-card {
                width: 100%;
                min-width: 0;
                min-height: 0;
                aspect-ratio: auto;
                grid-row: span 1;
                position: relative;
                display: block;
                padding: 0;
                overflow: hidden;
                border: 1px solid rgba(236, 236, 244, 0.96);
                border-radius: 14px;
                background: #111;
                box-shadow: 0 8px 18px rgba(42, 42, 60, 0.08);
            }

            .webai-video-tool-page .webai-recent-item {
                flex: none;
            }

            .webai-video-tool-page .webai-media-item--portrait {
                grid-row: span 2;
            }

            @media (max-width: 900px) {
                .webai-video-tool-page .webai-recent-list {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                    grid-auto-rows: 145px;
                }
            }

            @media (max-width: 560px) {
                .webai-video-tool-page .webai-recent-list {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    grid-auto-rows: 120px;
                    gap: 12px;
                }
            }

            .webai-video-tool-page .webai-recent-item video,
            .webai-video-tool-page .webai-generation-media,
            .webai-video-tool-page .webai-generation-media video {
                width: 100%;
                min-width: 0;
                max-width: none;
                height: 100%;
                max-height: none;
                display: block;
                border-radius: 0;
                object-fit: contain;
            }

            .webai-video-tool-page .webai-recent-item img,
            .webai-video-tool-page .webai-generation-media img {
                width: 100%;
                height: 100%;
                display: block;
                border-radius: 0;
                object-fit: cover;
            }

            .webai-video-tool-page .webai-recent-item video:fullscreen,
            .webai-video-tool-page .webai-generation-media video:fullscreen,
            .webai-video-tool-page .webai-recent-item video:-webkit-full-screen,
            .webai-video-tool-page .webai-generation-media video:-webkit-full-screen {
                width: 100vw !important;
                height: 100vh !important;
                background: #000;
                object-fit: contain !important;
            }

            .webai-video-tool-page .webai-generation-media {
                display: block;
            }

            .webai-video-tool-page .webai-generation-status {
                position: absolute;
                z-index: 1;
                inset: 50% auto auto 50%;
                min-height: 0;
                margin: 0;
                color: #fff;
                font-size: 14px;
                transform: translate(-50%, -50%);
            }

            .webai-video-tool-page .webai-generation-card.is-completed .webai-generation-status {
                display: none;
            }

            .webai-video-tool-page .webai-generation-card.is-loading .webai-generation-status {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                white-space: nowrap;
            }

            .webai-video-tool-page .webai-generation-card.is-loading .webai-generation-status::before {
                width: 18px;
                height: 18px;
                content: '';
                border: 2px solid rgba(255, 255, 255, 0.3);
                border-top-color: #fff;
                border-radius: 50%;
                animation: webai-button-spin 0.8s linear infinite;
            }

            .webai-video-delete {
                position: absolute;
                z-index: 4;
                top: 6px;
                right: 6px;
                width: 26px;
                height: 26px;
                display: grid;
                place-items: center;
                padding: 0;
                border: 0;
                border-radius: 50%;
                background: rgba(26, 27, 35, 0.76);
                color: #fff;
                font-size: 21px;
                font-weight: 300;
                line-height: 1;
                cursor: pointer;
                opacity: 0;
                transform: scale(0.86);
                transition: opacity 0.18s ease, transform 0.18s ease, background-color 0.18s ease;
            }

            .webai-video-tool-page .webai-recent-item:hover .webai-video-delete,
            .webai-video-tool-page .webai-generation-card.is-completed:hover .webai-video-delete,
            .webai-video-tool-page .webai-recent-item:hover .webai-generation-download,
            .webai-video-tool-page .webai-generation-card.is-completed:hover .webai-generation-download,
            .webai-generation-download:focus-visible,
            .webai-video-delete:focus-visible {
                opacity: 1;
                transform: scale(1);
            }

            .webai-video-delete:hover,
            .webai-video-delete:focus-visible {
                outline: 0;
                background: #d84343;
            }

            .webai-video-delete-modal[hidden] {
                display: none;
            }

            .webai-video-delete-modal {
                position: fixed;
                z-index: 1200;
                inset: 0;
                display: grid;
                place-items: center;
                padding: 20px;
            }

            .webai-video-delete-modal__backdrop {
                position: absolute;
                inset: 0;
                background: rgba(22, 24, 36, 0.58);
                backdrop-filter: blur(4px);
            }

            .webai-video-delete-modal__dialog {
                position: relative;
                z-index: 1;
                width: min(410px, 100%);
                padding: 28px;
                border-radius: 16px;
                background: #fff;
                box-shadow: 0 24px 70px rgba(0, 0, 0, 0.28);
            }

            .webai-video-delete-modal__dialog h2 {
                margin: 0;
                color: #2f303d;
                font-size: 23px;
            }

            .webai-video-delete-modal__dialog p {
                margin: 12px 0 24px;
                color: #646674;
                font-size: 14px;
                line-height: 1.55;
            }

            .webai-video-delete-modal__actions {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
            }

            .webai-video-delete-modal__actions button {
                min-height: 38px;
                padding: 0 15px;
                border-radius: 9px;
                font-size: 13px;
                font-weight: 800;
                cursor: pointer;
            }

            .webai-video-delete-modal__cancel {
                border: 1px solid #dedee6;
                background: #fff;
                color: #5e6070;
            }

            .webai-video-delete-modal__confirm {
                border: 1px solid #d84343;
                background: #d84343;
                color: #fff;
            }

            .webai-composer {
                width: min(1040px, calc(100% - 140px));
                position: sticky;
                bottom: 18px;
                z-index: 20;
                margin: auto auto 0;
                padding: 12px;
                border: 1px solid rgba(255, 255, 255, 0.95);
                border-radius: 26px;
                background: rgba(255, 255, 255, 0.92);
                box-shadow: var(--webai-shadow);
            }

            .webai-prompt {
                width: 100%;
                min-height: 70px;
                margin-bottom: 12px;
                padding: 14px 16px;
                resize: vertical;
                border: 1px solid rgba(228, 228, 238, 0.98);
                border-radius: 9px;
                outline: 0;
                background: rgba(255, 255, 255, 0.9);
                color: var(--webai-text);
                font-size: 15px;
                line-height: 1.5;
                box-shadow:
                    inset 0 2px 8px rgba(70, 70, 90, 0.08),
                    0 1px 0 rgba(255, 255, 255, 0.8);
            }

            .webai-prompt::placeholder {
                color: #4f4f5d;
                opacity: 1;
            }

            .webai-video-expiry-notice {
                width: min(680px, calc(100% - 48px));
                margin: 0 auto 10px;
                overflow: hidden;
                color: #dc2626;
                font-size: 13px;
                font-weight: 650;
                line-height: 1.4;
                white-space: nowrap;
            }

            .webai-video-expiry-notice__track {
                display: inline-block;
                padding-left: 100%;
                animation: webai-video-expiry-ticker 14s linear infinite;
                will-change: transform;
            }

            @keyframes webai-video-expiry-ticker {
                to {
                    transform: translateX(-100%);
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .webai-video-expiry-notice__track {
                    padding-left: 0;
                    animation: none;
                }
            }

            .webai-media-fields {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 8px;
                margin: -4px 0 12px;
            }

            .webai-media-fields[hidden],
            .webai-media-field[hidden],
            .webai-media-clear[hidden] {
                display: none !important;
            }

            .webai-media-field {
                display: flex;
                align-items: center;
                gap: 7px;
                flex-wrap: wrap;
                min-height: 34px;
                padding: 4px 8px;
                border: 1px solid rgba(255, 122, 0, 0.5);
                border-radius: 9px;
                background: rgba(255, 255, 255, 0.72);
                box-shadow: 0 3px 8px rgba(255, 122, 0, 0.1);
            }

            .webai-media-field span {
                flex: 0 0 auto;
                color: var(--webai-text);
                font-size: 13px;
                font-weight: 500;
                white-space: nowrap;
            }

            .webai-media-field strong {
                color: var(--webai-accent);
                font-weight: 700;
            }

            .webai-media-field [data-webai-media-preview] {
                display: none;
            }

            .webai-media-pick,
            .webai-media-clear {
                flex: 0 0 auto;
                min-height: 24px;
                border: 1px solid rgba(255, 122, 0, 0.65);
                border-radius: 7px;
                background: rgba(255, 255, 255, 0.9);
                color: var(--webai-accent);
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
            }

            .webai-media-pick {
                position: relative;
                padding: 0 8px;
            }

            .webai-media-pick[data-webai-tooltip]::after {
                content: attr(data-webai-tooltip);
                position: absolute;
                left: 50%;
                bottom: calc(100% + 10px);
                width: 280px;
                padding: 9px 10px;
                border: 1px solid rgba(255, 122, 0, 0.34);
                border-radius: 8px;
                background: rgba(255, 255, 255, 0.96);
                color: var(--webai-text);
                font-size: 12px;
                font-weight: 500;
                line-height: 1.35;
                text-align: left;
                white-space: normal;
                box-shadow: 0 10px 24px rgba(42, 42, 60, 0.14);
                opacity: 0;
                pointer-events: none;
                transform: translate(-50%, 4px);
                transition: opacity 0.18s ease, transform 0.18s ease;
                z-index: 60;
            }

            .webai-media-pick[data-webai-tooltip]:hover::after,
            .webai-media-pick[data-webai-tooltip]:focus-visible::after {
                opacity: 1;
                transform: translate(-50%, 0);
            }

            .webai-media-clear {
                width: 24px;
                padding: 0;
            }

            .webai-media-field em {
                min-width: 0;
                flex: 1 1 auto;
                overflow: hidden;
                color: var(--webai-dim);
                font-size: 12px;
                font-style: normal;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .webai-media-image-preview,
            .webai-media-video-preview {
                margin-left: auto;
                width: 60px;
                height: 60px;
                flex: 0 0 60px;
                border: 1px solid rgba(255, 122, 0, 0.45);
                border-radius: 6px;
                object-fit: cover;
            }

            .webai-media-note {
                flex: 0 0 100%;
                color: var(--webai-dim);
                font-size: 11px;
                line-height: 1.45;
            }

            .webai-toolbar {
                display: flex;
                align-items: center;
                gap: 9px;
                flex-wrap: wrap;
            }

            .webai-filter-toggle {
                display: none;
            }

            .webai-filter-panel {
                display: contents;
            }

            .webai-pill,
            .webai-select,
            .webai-cost,
            .webai-send {
                min-height: 32px;
                border: 1px solid var(--webai-accent);
                border-radius: 9px;
                background: rgba(255, 255, 255, 0.82);
                color: var(--webai-text);
                font-size: 14px;
                font-weight: 500;
                box-shadow: 0 3px 8px rgba(255, 122, 0, 0.16);
            }

            .webai-pill {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                padding: 0 9px;
            }

            .webai-pill strong {
                color: var(--webai-text);
                font-weight: 500;
            }

            .webai-select {
                padding: 0 28px 0 10px;
                color: var(--webai-text);
                appearance: none;
                background-image:
                    linear-gradient(45deg, transparent 50%, var(--webai-accent) 50%),
                    linear-gradient(135deg, var(--webai-accent) 50%, transparent 50%);
                background-position:
                    calc(100% - 14px) 13px,
                    calc(100% - 9px) 13px;
                background-size: 5px 5px, 5px 5px;
                background-repeat: no-repeat;
            }

            .webai-model-picker {
                position: relative;
                min-width: 220px;
            }

            .webai-model-select {
                position: absolute;
                width: 1px;
                height: 1px;
                overflow: hidden;
                clip: rect(0 0 0 0);
                clip-path: inset(50%);
                white-space: nowrap;
            }

            .webai-model-picker__trigger {
                width: 100%;
                min-height: 46px;
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                justify-content: center;
                gap: 1px;
                padding: 6px 38px 6px 13px;
                border: 1px solid rgba(255, 122, 0, 0.75);
                border-radius: 11px;
                background: linear-gradient(135deg, #fff, #fff7f0);
                color: var(--webai-text);
                box-shadow: 0 4px 12px rgba(255, 122, 0, 0.14);
                cursor: pointer;
                text-align: left;
            }

            .webai-model-picker__trigger::after {
                content: '';
                position: absolute;
                top: 19px;
                right: 14px;
                width: 7px;
                height: 7px;
                border-right: 2px solid var(--webai-accent);
                border-bottom: 2px solid var(--webai-accent);
                transform: rotate(45deg);
                transition: transform 0.18s ease;
            }

            .webai-model-picker.is-open .webai-model-picker__trigger::after {
                transform: translateY(3px) rotate(225deg);
            }

            .webai-model-picker__trigger span {
                color: var(--webai-accent);
                font-size: 10px;
                font-weight: 850;
                letter-spacing: 0.07em;
                text-transform: uppercase;
            }

            .webai-model-picker__trigger strong {
                max-width: 100%;
                overflow: hidden;
                color: #383946;
                font-size: 14px;
                font-weight: 800;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .webai-model-picker__menu {
                position: absolute;
                z-index: 50;
                bottom: calc(100% + 8px);
                left: 0;
                width: min(310px, calc(100vw - 32px));
                max-height: 320px;
                overflow-y: auto;
                padding: 8px;
                border: 1px solid rgba(255, 122, 0, 0.34);
                border-radius: 12px;
                background: #fff;
                box-shadow: 0 18px 38px rgba(39, 39, 52, 0.2);
            }

            .webai-model-picker__group + .webai-model-picker__group {
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px solid #f0e7df;
            }

            .webai-model-picker__group > span {
                display: block;
                padding: 4px 8px 6px;
                color: var(--webai-accent);
                font-size: 11px;
                font-weight: 900;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .webai-model-picker__group button {
                width: 100%;
                display: block;
                padding: 9px 10px 9px 22px;
                border: 0;
                border-radius: 8px;
                background: transparent;
                color: #41424f;
                cursor: pointer;
                font-size: 14px;
                font-weight: 650;
                text-align: left;
            }

            .webai-model-picker__group button:hover,
            .webai-model-picker__group button:focus-visible {
                outline: 0;
                background: #fff1e0;
                color: #d66000;
            }

            .webai-model-picker__group button.is-selected {
                position: relative;
                padding-right: 34px;
                background: #fff1e0;
                color: #d66000;
                font-weight: 850;
            }

            .webai-model-picker__group button.is-selected::after {
                content: '✓';
                position: absolute;
                top: 50%;
                right: 12px;
                color: var(--webai-accent);
                font-size: 14px;
                font-weight: 950;
                transform: translateY(-50%);
            }

            .webai-spacer {
                flex: 1 1 auto;
            }

            .webai-cost {
                display: inline-flex;
                align-items: center;
                padding: 0 12px;
                color: var(--webai-text);
                background: rgba(255, 255, 255, 0.82);
            }

            .webai-send {
                width: 34px;
                min-width: 34px;
                position: relative;
                border-radius: 10px;
                background: rgba(255, 255, 255, 0.84);
                border: 1px solid rgba(255, 122, 0, 0.62);
                color: var(--webai-accent);
                font-size: 20px;
                cursor: pointer;
                box-shadow: 0 7px 15px rgba(255, 122, 0, 0.18);
                animation: webai-send-bob 1.7s ease-in-out infinite;
                transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
            }

            .webai-send:hover:not(:disabled),
            .webai-send:focus-visible:not(:disabled) {
                background: var(--webai-accent);
                border-color: var(--webai-accent);
                color: #fff;
                outline: 0;
                animation-play-state: paused;
                transform: translateY(-1px);
            }

            .webai-send.is-loading {
                color: transparent;
                cursor: wait;
                animation: none;
            }

            .webai-send.is-loading::after {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                width: 14px;
                height: 14px;
                border: 2px solid rgba(255, 122, 0, 0.25);
                border-top-color: var(--webai-accent);
                border-radius: 50%;
                animation: webai-button-spin 0.7s linear infinite;
                transform: translate(-50%, -50%);
            }

            @keyframes webai-button-spin {
                to { transform: translate(-50%, -50%) rotate(360deg); }
            }

            @keyframes webai-send-bob {
                0%, 100% {
                    border-color: rgba(255, 139, 35, 0.95);
                    box-shadow: 0 11px 24px rgba(255, 102, 0, 0.48), 0 0 0 4px rgba(255, 137, 26, 0.20);
                    transform: translateY(0);
                }

                50% {
                    border-color: rgba(255, 122, 0, 0.52);
                    box-shadow: 0 3px 7px rgba(255, 122, 0, 0.20);
                    transform: translateY(-5px);
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .webai-send {
                    animation: none;
                }
            }

            .webai-page {
                border: 1px solid var(--webai-border);
                border-radius: 12px;
                padding: 28px;
                background: var(--webai-panel-soft);
            }

            .webai-auth-page {
                min-height: calc(100vh - 48px);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 34px 0;
            }

            .webai-auth-shell {
                width: min(1080px, 100%);
                display: grid;
                grid-template-columns: minmax(0, 0.86fr) minmax(360px, 1fr);
                gap: 30px;
                align-items: stretch;
            }

            .webai-auth-copy {
                display: flex;
                flex-direction: column;
                justify-content: center;
                min-height: 520px;
                padding: 42px;
                border: 1px solid rgba(235, 235, 244, 0.95);
                border-radius: 14px;
                background:
                    radial-gradient(circle at 18% 8%, rgba(255, 122, 0, 0.1), transparent 17rem),
                    linear-gradient(180deg, rgba(255, 255, 255, 0.82), rgba(247, 247, 252, 0.78));
                box-shadow: var(--webai-shadow);
            }

            .webai-auth-copy h1 {
                max-width: 460px;
                margin: 0;
                color: #545463;
                font-size: 42px;
                line-height: 1.12;
                font-weight: 950;
                letter-spacing: 0;
                text-shadow: 0 2px 4px rgba(80, 80, 100, 0.14);
            }

            .webai-auth-copy p {
                max-width: 430px;
                margin: 22px 0 0;
                color: #4f4f5d;
                font-size: 18px;
                line-height: 1.55;
                font-weight: 600;
            }

            .webai-auth-form {
                min-width: 0;
            }

            .webai-auth-form .container {
                width: 100%;
                max-width: none;
                padding: 0;
            }

            .webai-auth-form .row.justify-content-center {
                margin: 0;
                padding: 0 !important;
            }

            .webai-auth-form .row.justify-content-center > [class*="col-"] {
                width: 100%;
                max-width: none;
                flex: 0 0 100%;
                padding: 0;
            }

            .webai-auth-form .auth-card {
                min-height: 520px;
                overflow: hidden;
                border: 1px solid rgba(235, 235, 244, 0.95);
                border-radius: 14px;
                background: rgba(255, 255, 255, 0.76) !important;
                box-shadow: var(--webai-shadow);
            }

            .webai-auth-form .auth-card.card {
                padding: 0;
            }

            .webai-auth-form .auth-card__horizontal {
                display: block;
                margin: 0;
            }

            .webai-auth-form .auth-card__left {
                display: none;
            }

            .webai-auth-form .auth-card__right {
                width: 100%;
                max-width: none;
                padding: 0;
            }

            .webai-auth-form .auth-card__header {
                padding: 34px 34px 12px;
            }

            .webai-auth-form .auth-card__header > div {
                align-items: center !important;
            }

            .webai-auth-form .auth-card__header-icon {
                width: 48px;
                height: 48px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 auto;
                border: 1px solid rgba(255, 122, 0, 0.6);
                border-radius: 10px !important;
                background: rgba(255, 247, 237, 0.78) !important;
                color: var(--webai-accent);
            }

            .webai-auth-form .auth-card__header-icon svg {
                color: var(--webai-accent) !important;
            }

            .webai-auth-form .auth-card__header-title {
                margin: 0 0 6px !important;
                color: #545463;
                font-size: 26px !important;
                line-height: 1.15;
                font-weight: 900;
            }

            .webai-auth-form .auth-card__header-description {
                margin: 0;
                color: #5c5c68 !important;
                font-size: 14px;
                line-height: 1.5;
                font-weight: 500;
            }

            .webai-auth-form .auth-card__body {
                padding: 18px 34px 34px;
            }

            .webai-auth-form form {
                width: 100%;
            }

            .webai-auth-form .mb-3 {
                margin-bottom: 16px;
            }

            .webai-auth-form .mt-3 {
                margin-top: 18px;
            }

            .webai-auth-form .text-center {
                text-align: center;
            }

            .webai-auth-form .text-end {
                text-align: right;
            }

            .webai-auth-form .d-flex {
                display: flex;
            }

            .webai-auth-form .flex-column {
                flex-direction: column;
            }

            .webai-auth-form .align-items-start {
                align-items: flex-start;
            }

            .webai-auth-form .gap-3 {
                gap: 16px;
            }

            .webai-auth-form .ms-1 {
                margin-left: 5px;
            }

            .webai-auth-form .row {
                display: flex;
                flex-wrap: wrap;
            }

            .webai-auth-form .row.g-0 {
                margin-left: 0;
                margin-right: 0;
            }

            .webai-auth-form .row.g-0 > [class*="col-"] {
                padding-left: 0;
                padding-right: 0;
            }

            .webai-auth-form .col-6 {
                width: 50%;
                flex: 0 0 auto;
            }

            .webai-auth-form .form-label {
                margin-bottom: 7px;
                color: #424252;
                font-size: 13px;
                font-weight: 800;
            }

            .webai-auth-form .form-control,
            .webai-auth-form .form-select {
                width: 100%;
                min-height: 48px;
                border: 1px solid rgba(228, 228, 238, 0.98);
                border-radius: 9px;
                background: rgba(255, 255, 255, 0.72);
                color: var(--webai-text);
                font-size: 14px;
                font-weight: 500;
                box-shadow:
                    inset 0 2px 8px rgba(70, 70, 90, 0.06),
                    0 1px 0 rgba(255, 255, 255, 0.8);
            }

            .webai-auth-form .form-control:focus,
            .webai-auth-form .form-select:focus {
                border-color: var(--webai-accent);
                box-shadow: 0 0 0 3px rgba(255, 122, 0, 0.12);
                outline: 0;
            }

            .webai-auth-form .form-control::placeholder {
                color: #777783;
                opacity: 1;
            }

            .webai-auth-form .position-relative {
                position: relative;
                width: 100%;
            }

            .webai-auth-form .auth-input-icon {
                position: absolute;
                left: 14px;
                top: 50%;
                z-index: 2;
                width: 22px;
                height: 22px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0;
                border: 0;
                background: transparent;
                color: var(--webai-accent);
                transform: translateY(-50%);
                pointer-events: none;
            }

            .webai-auth-form .auth-input-icon svg {
                width: 19px;
                height: 19px;
            }

            .webai-auth-form .auth-input-icon + .form-control,
            .webai-auth-form .form-control.ps-5 {
                padding-left: 46px;
            }

            .webai-auth-form .auth-input-icon + .iti {
                width: 100%;
                padding-left: 0;
            }

            .webai-auth-form .auth-input-icon + .iti .iti__input {
                padding-left: 46px !important;
            }

            .webai-auth-form .iti {
                width: 100%;
                display: block;
            }

            .webai-auth-form .form-check {
                display: flex;
                align-items: center;
                gap: 8px;
                min-height: 24px;
                margin: 0;
                padding-left: 0;
            }

            .webai-auth-form .form-check-input {
                width: 18px;
                height: 18px;
                margin: 0;
                border: 1px solid rgba(255, 122, 0, 0.6);
                border-radius: 5px;
            }

            .webai-auth-form .form-check-input:checked {
                border-color: var(--webai-accent);
                background-color: var(--webai-accent);
            }

            .webai-auth-form .form-check-label,
            .webai-auth-form .form-check label {
                color: #5c5c68;
                font-size: 14px;
                font-weight: 600;
            }

            .webai-auth-form a {
                color: var(--webai-accent);
                font-weight: 800;
                text-decoration: none !important;
            }

            .webai-auth-form a:hover {
                color: #d46400;
            }

            .webai-auth-form .btn,
            .webai-auth-form button[type="submit"] {
                min-height: 50px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                border: 1px solid var(--webai-accent);
                border-radius: 10px;
                background: var(--webai-accent);
                color: #ffffff;
                font-size: 16px;
                font-weight: 900;
                box-shadow: 0 10px 20px rgba(255, 122, 0, 0.18);
            }

            .webai-auth-form .btn:hover,
            .webai-auth-form button[type="submit"]:hover {
                border-color: #d46400;
                background: #d46400;
                color: #ffffff;
            }

            .webai-auth-form .alert {
                border-radius: 9px;
                font-size: 14px;
            }

            .webai-social-login {
                margin-bottom: 18px;
            }

            .webai-google-login {
                min-height: 48px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                width: 100%;
                border: 1px solid rgba(228, 228, 238, 0.98);
                border-radius: 10px;
                background: rgba(255, 255, 255, 0.84);
                color: #424252 !important;
                font-size: 14px;
                font-weight: 800;
                text-decoration: none;
                box-shadow: 0 10px 24px rgba(70, 70, 88, 0.08);
            }

            .webai-google-login:hover {
                border-color: rgba(255, 122, 0, 0.55);
                color: var(--webai-accent) !important;
                box-shadow: 0 14px 28px rgba(255, 122, 0, 0.14);
            }

            .webai-google-login__icon {
                width: 24px;
                height: 24px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 auto;
                border: 1px solid rgba(228, 228, 238, 0.98);
                border-radius: 50%;
                background: #ffffff;
                color: #4285f4;
                font-size: 14px;
                font-weight: 900;
            }

            .webai-auth-divider {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-top: 16px;
                color: #777783;
                font-size: 13px;
                font-weight: 700;
                text-align: center;
            }

            .webai-auth-divider::before,
            .webai-auth-divider::after {
                content: "";
                height: 1px;
                flex: 1 1 auto;
                background: rgba(228, 228, 238, 0.98);
            }

            .webai-home-hero {
                position: relative;
                min-height: 392px;
                display: grid;
                grid-template-columns: minmax(0, 1fr) 420px;
                gap: 28px;
                overflow: hidden;
                border: 1px solid rgba(235, 235, 244, 0.95);
                border-radius: 10px;
                background:
                    radial-gradient(circle at 70% 34%, rgba(255, 122, 0, 0.08), transparent 18rem),
                    linear-gradient(90deg, rgba(255, 255, 255, 0.88), rgba(247, 247, 252, 0.84));
                box-shadow: var(--webai-shadow);
            }

            .webai-hero-copy {
                position: relative;
                z-index: 1;
                align-self: center;
                max-width: 590px;
                padding: 36px 0 38px 48px;
            }

            .webai-status {
                width: fit-content;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 34px;
                padding: 5px 14px;
                border: 1px solid rgba(255, 122, 0, 0.46);
                border-radius: 999px;
                background: rgba(255, 247, 237, 0.82);
                color: var(--webai-accent);
                font-size: 13px;
                font-weight: 850;
            }

            .webai-status span {
                width: 7px;
                height: 7px;
                border-radius: 50%;
                background: var(--webai-accent);
                box-shadow: 0 0 18px rgba(255, 122, 0, 0.42);
            }

            .webai-hero-copy h1 {
                margin: 0;
                color: #545463;
                font-size: 42px;
                line-height: 1.14;
                font-weight: 900;
                letter-spacing: 0;
            }

            .webai-hero-copy h1 span {
                color: var(--webai-accent);
                text-shadow: 0 4px 14px rgba(255, 122, 0, 0.16);
            }

            .webai-hero-copy p {
                max-width: 560px;
                margin: 24px 0 32px;
                color: #4f4f5d;
                font-size: 19px;
                line-height: 1.5;
                font-weight: 600;
            }

            .webai-primary-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 48px;
                padding: 0 25px;
                border-radius: 8px;
                border: 1px solid var(--webai-accent);
                background: rgba(255, 255, 255, 0.82);
                color: var(--webai-accent);
                text-decoration: none;
                font-weight: 900;
                box-shadow: 0 8px 18px rgba(255, 122, 0, 0.16);
            }

            .webai-hero-visual {
                position: relative;
                min-height: 392px;
                color: rgba(255, 122, 0, 0.52);
                opacity: 0.76;
                filter: drop-shadow(0 0 16px rgba(255, 122, 0, 0.14));
            }

            .webai-hero-visual svg {
                position: absolute;
                top: -10px;
                right: 10px;
                width: 400px;
                height: auto;
            }

            .webai-hero-visual img {
                position: absolute;
                inset: 0;
                width: 100%;
                height: 100%;
                border-radius: 18px;
                object-fit: cover;
            }

            .webai-tools-section {
                margin-top: 46px;
            }

            .webai-tools-section h2 {
                margin: 0 0 22px;
                font-size: 22px;
                font-weight: 900;
            }

            .webai-tool-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 24px;
            }

            .webai-content-posts { margin-top: 48px; }
            .webai-content-posts__heading { margin-bottom: 20px; }
            .webai-content-posts__heading span { display: block; margin-bottom: 5px; color: var(--webai-accent); font-size: 12px; font-weight: 900; letter-spacing: 0.1em; text-transform: uppercase; }
            .webai-content-posts__heading h2 { margin: 0; color: #393a47; font-size: 24px; }
            .webai-content-posts__grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
            .webai-content-post { overflow: hidden; border: 1px solid rgba(235, 235, 244, 0.96); border-radius: 16px; background: rgba(255, 255, 255, 0.78); box-shadow: var(--webai-shadow); }
            .webai-content-post > img { width: 100%; height: 180px; display: block; object-fit: cover; }
            .webai-content-post__body { padding: 20px; }
            .webai-content-post__body h3 { margin: 0 0 8px; color: #363744; font-size: 19px; }
            .webai-content-post__body p { margin: 0; color: var(--webai-muted); font-size: 14px; line-height: 1.6; }
            .webai-content-post__body a { display: inline-flex; gap: 7px; margin-top: 15px; color: var(--webai-accent); font-size: 14px; font-weight: 850; text-decoration: none; }

            .webai-tool-card {
                display: flex;
                min-height: 322px;
                flex-direction: column;
                padding: 24px;
                border: 1px solid rgba(235, 235, 244, 0.95);
                border-radius: 10px;
                background:
                    radial-gradient(circle at 12% 0%, rgba(255, 122, 0, 0.06), transparent 13rem),
                    rgba(255, 255, 255, 0.72);
                box-shadow: var(--webai-shadow);
            }

            .webai-tool-icon {
                width: 44px;
                height: 44px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 auto;
                border: 1px solid rgba(255, 122, 0, 0.75);
                border-radius: 8px;
                background: rgba(255, 247, 237, 0.74);
                color: var(--webai-accent);
            }

            .webai-tool-card-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 18px;
                margin-bottom: 24px;
            }

            .webai-tool-card-head h3 {
                margin: 0;
                font-size: 22px;
                line-height: 1.2;
                font-weight: 900;
            }

            .webai-tool-card p {
                margin: 0;
                color: #4f4f5d;
                font-size: 16px;
                line-height: 1.55;
                font-weight: 600;
            }

            .webai-tool-footer {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                margin-top: auto;
                padding-top: 24px;
                border-top: 1px solid rgba(215, 215, 225, 0.8);
                color: #545463;
                font-weight: 850;
            }

            .webai-tool-footer span {
                color: #545463;
            }

            .webai-tool-footer a {
                width: 42px;
                height: 42px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                border: 1px solid var(--webai-accent);
                background: rgba(255, 255, 255, 0.86);
                color: var(--webai-accent);
                text-decoration: none;
                font-size: 0;
                font-weight: 900;
                line-height: 1;
                transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
            }

            .webai-tool-footer a::before {
                content: "";
                width: 20px;
                height: 20px;
                display: block;
                background-color: currentColor;
                mask: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill='black' d='M20 11H7.83l5.59-5.59L12 4 4 12l8 8 1.42-1.41L7.83 13H20v-2Z'/%3E%3C/svg%3E") center / contain no-repeat;
                -webkit-mask: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill='black' d='M20 11H7.83l5.59-5.59L12 4 4 12l8 8 1.42-1.41L7.83 13H20v-2Z'/%3E%3C/svg%3E") center / contain no-repeat;
                transform-origin: center;
                transition: transform 0.2s ease;
            }

            .webai-tool-card:hover .webai-tool-footer a,
            .webai-tool-card:focus-within .webai-tool-footer a {
                background: var(--webai-accent);
                color: #ffffff;
            }

            .webai-tool-card:hover .webai-tool-footer a::before,
            .webai-tool-card:focus-within .webai-tool-footer a::before {
                transform: rotate(180deg);
            }

            .webai-credit-page {
                min-height: calc(100vh - 48px);
                display: flex;
                flex-direction: column;
                justify-content: center;
                gap: 38px;
                padding: 44px 56px 56px;
                border-radius: 14px;
                background:
                    radial-gradient(circle at 50% 18%, rgba(255, 122, 0, 0.08), transparent 25rem),
                    linear-gradient(180deg, rgba(255, 255, 255, 0.82), rgba(247, 247, 252, 0.78));
                color: var(--webai-text);
                box-shadow: var(--webai-shadow);
            }

            .webai-credit-header {
                text-align: center;
            }

            .webai-credit-header h1 {
                margin: 0 0 10px;
                font-size: 38px;
                line-height: 1.1;
                font-weight: 900;
                color: #545463;
                text-shadow: 0 2px 4px rgba(80, 80, 100, 0.14);
            }

            .webai-credit-header p {
                margin: 0 0 8px;
                color: #4f4f5d;
                font-size: 20px;
                font-weight: 600;
            }

            .webai-credit-header strong {
                color: var(--webai-accent);
                font-size: 16px;
                font-weight: 900;
            }

            .webai-pricing-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 28px;
                width: min(1120px, 100%);
                margin: 0 auto;
            }

            .webai-pricing-card {
                position: relative;
                min-height: 392px;
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 38px 28px 28px;
                border: 1px solid rgba(235, 235, 244, 0.95);
                border-radius: 18px;
                background:
                    radial-gradient(circle at 50% 0%, rgba(255, 122, 0, 0.05), transparent 14rem),
                    rgba(255, 255, 255, 0.76);
                color: var(--webai-text);
                text-align: center;
                box-shadow: var(--webai-shadow);
                transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
            }

            .webai-pricing-card:hover,
            .webai-pricing-card:focus-within {
                border-color: var(--webai-accent);
                box-shadow: 0 0 0 1px rgba(255, 122, 0, 0.26), 0 28px 70px rgba(255, 122, 0, 0.14);
            }

            .webai-pricing-card:hover,
            .webai-pricing-card:focus-within {
                transform: translateY(-4px);
            }

            .webai-pricing-card h2 {
                margin: 0 0 16px;
                font-size: 25px;
                line-height: 1.15;
                font-weight: 900;
                color: #545463;
            }

            .webai-pricing-card p {
                min-height: 46px;
                margin: 0 0 38px;
                color: #4f4f5d;
                font-size: 17px;
                line-height: 1.35;
                font-weight: 650;
            }

            .webai-price {
                margin-bottom: 20px;
                color: #2f2f3a;
                font-size: 36px;
                line-height: 1;
                font-weight: 950;
                text-shadow: 0 2px 0 rgba(255, 122, 0, 0.14);
            }

            .webai-credit-amount {
                min-width: 168px;
                margin-bottom: auto;
                padding: 11px 18px;
                border: 1px solid rgba(255, 122, 0, 0.35);
                border-radius: 8px;
                background: rgba(255, 122, 0, 0.1);
                color: var(--webai-accent);
                font-size: 23px;
                line-height: 1;
                font-weight: 950;
            }

            .webai-buy-button {
                width: 100%;
                min-height: 54px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                margin-top: 44px;
                border: 1px solid rgba(255, 122, 0, 0.45);
                border-radius: 12px;
                background: rgba(255, 255, 255, 0.86);
                color: var(--webai-accent);
                text-decoration: none;
                font-size: 18px;
                font-weight: 900;
                opacity: 0;
                visibility: hidden;
                transform: translateY(10px);
                transition: opacity 0.2s ease, visibility 0.2s ease, transform 0.2s ease, background 0.2s ease, color 0.2s ease;
            }

            .webai-pricing-card form {
                width: 100%;
            }

            .webai-pricing-card:hover .webai-buy-button,
            .webai-pricing-card:focus-within .webai-buy-button {
                border-color: var(--webai-accent);
                background: var(--webai-accent);
                color: #ffffff;
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }

            .webai-credit-payment-page {
                width: min(900px, calc(100% - 40px));
                margin: 42px auto 64px;
            }

            .webai-credit-payment-back {
                display: inline-flex;
                margin-bottom: 18px;
                color: #5f6170;
                font-size: 14px;
                font-weight: 750;
            }

            .webai-credit-payment-card {
                display: grid;
                grid-template-columns: minmax(0, 1.12fr) minmax(260px, 0.88fr);
                overflow: hidden;
                border-radius: 20px;
                background: #fff;
                box-shadow: 0 18px 55px rgba(38, 40, 55, 0.12);
            }

            .webai-credit-payment-details {
                padding: 42px 48px;
            }

            .webai-credit-payment-eyebrow {
                display: block;
                margin-bottom: 10px;
                color: var(--webai-accent);
                font-size: 12px;
                font-weight: 850;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .webai-credit-payment-details h1 {
                margin: 0 0 28px;
                color: #252633;
                font-size: 30px;
            }

            .webai-credit-payment-list {
                display: grid;
                gap: 13px;
                margin: 0;
            }

            .webai-credit-payment-list > div {
                display: grid;
                grid-template-columns: minmax(115px, 0.8fr) minmax(0, 1.2fr);
                gap: 14px;
                align-items: baseline;
            }

            .webai-credit-payment-list dt {
                color: #777989;
                font-size: 14px;
            }

            .webai-credit-payment-list dd {
                min-width: 0;
                margin: 0;
                color: #343542;
                font-weight: 760;
                overflow-wrap: anywhere;
                text-align: right;
            }

            .webai-credit-payment-total {
                margin-top: 8px;
                padding-top: 16px;
                border-top: 1px solid #ececf1;
            }

            .webai-credit-payment-total dd {
                color: var(--webai-accent);
                font-size: 23px;
                font-weight: 900;
            }

            .webai-credit-payment-qr {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 16px;
                padding: 42px 32px;
                background: linear-gradient(145deg, #fff6ed, #fff 65%);
                color: #5b5d6b;
                font-size: 14px;
                font-weight: 800;
                text-align: center;
            }

            .webai-credit-payment-qr img {
                width: min(220px, 100%);
                aspect-ratio: 1;
                border: 1px solid rgba(255, 122, 0, 0.3);
                border-radius: 14px;
                background: #fff;
                object-fit: contain;
            }

            .webai-credit-payment-qr small {
                max-width: 230px;
                color: #8d7c70;
                font-size: 12px;
                font-weight: 650;
                line-height: 1.45;
            }

            .webai-credit-payment-qr-empty {
                width: min(220px, 100%);
                aspect-ratio: 1;
                display: grid;
                place-content: center;
                gap: 10px;
                padding: 18px;
                border: 2px dashed rgba(255, 122, 0, 0.45);
                border-radius: 14px;
                color: var(--webai-accent);
            }

            .webai-credit-payment-qr-empty small {
                color: #8d7c70;
            }

            .webai-credit-payment-success {
                display: grid;
                justify-items: center;
                gap: 12px;
                max-width: 230px;
                color: #188754;
            }

            .webai-credit-payment-success__icon {
                width: 94px;
                height: 94px;
                display: grid;
                place-items: center;
                border-radius: 50%;
                background: #e6f7ee;
                color: #19a560;
                font-size: 54px;
                font-weight: 900;
            }

            .webai-credit-payment-success strong {
                font-size: 19px;
            }

            .webai-credit-payment-success small,
            .webai-credit-payment-success em {
                color: #6f8379;
                font-size: 12px;
                font-style: normal;
                font-weight: 650;
                line-height: 1.45;
            }

            .webai-payment-modal[hidden] {
                display: none;
            }

            .webai-payment-modal {
                position: fixed;
                z-index: 1100;
                inset: 0;
                display: grid;
                place-items: center;
                padding: 24px;
            }

            .webai-payment-modal__backdrop {
                position: absolute;
                inset: 0;
                background: rgba(22, 24, 36, 0.58);
                backdrop-filter: blur(5px);
            }

            .webai-payment-modal__dialog {
                position: relative;
                z-index: 1;
                width: min(780px, 100%);
                display: grid;
                grid-template-columns: minmax(0, 1.12fr) minmax(240px, 0.88fr);
                overflow: hidden;
                border-radius: 18px;
                background: #fff;
                box-shadow: 0 28px 80px rgba(0, 0, 0, 0.3);
            }

            .webai-payment-modal__details {
                padding: 42px 44px;
            }

            .webai-payment-modal__eyebrow {
                display: block;
                margin-bottom: 10px;
                color: var(--webai-accent);
                font-size: 12px;
                font-weight: 850;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .webai-payment-modal h2 {
                margin: 0 0 28px;
                color: #252633;
                font-size: 29px;
                line-height: 1.15;
            }

            .webai-payment-modal__list {
                display: grid;
                gap: 13px;
                margin: 0;
            }

            .webai-payment-modal__list > div {
                display: grid;
                grid-template-columns: minmax(112px, 0.8fr) minmax(0, 1.2fr);
                gap: 14px;
                align-items: baseline;
            }

            .webai-payment-modal dt {
                color: #777989;
                font-size: 13px;
            }

            .webai-payment-modal dd {
                min-width: 0;
                margin: 0;
                color: #343542;
                font-size: 14px;
                font-weight: 760;
                overflow-wrap: anywhere;
                text-align: right;
            }

            .webai-payment-modal__total {
                margin-top: 8px;
                padding-top: 16px;
                border-top: 1px solid #ececf1;
            }

            .webai-payment-modal__total dd {
                color: var(--webai-accent);
                font-size: 22px;
                font-weight: 900;
            }

            .webai-payment-modal__qr {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 18px;
                padding: 42px 32px;
                background: linear-gradient(145deg, #fff6ed, #fff 65%);
                color: #5b5d6b;
                font-size: 14px;
                font-weight: 800;
            }

            .webai-payment-modal__qr-placeholder {
                width: 196px;
                aspect-ratio: 1;
                display: grid;
                place-content: center;
                gap: 12px;
                border: 2px dashed rgba(255, 122, 0, 0.5);
                border-radius: 14px;
                background: rgba(255, 255, 255, 0.72);
                color: var(--webai-accent);
                text-align: center;
            }

            .webai-payment-modal__qr-placeholder span {
                font-size: 42px;
                font-weight: 950;
                letter-spacing: 0.08em;
            }

            .webai-payment-modal__qr-placeholder small {
                max-width: 135px;
                color: #8d7c70;
                font-size: 11px;
                font-weight: 650;
                line-height: 1.45;
            }

            .webai-payment-modal__qr-image {
                width: 196px;
                aspect-ratio: 1;
                border: 1px solid rgba(255, 122, 0, 0.3);
                border-radius: 14px;
                background: #fff;
                object-fit: contain;
            }

            .webai-payment-modal__qr > span {
                order: 1;
            }

            .webai-payment-modal__qr-placeholder,
            .webai-payment-modal__qr-image {
                order: 2;
            }

            .webai-payment-modal__qr-placeholder[hidden],
            .webai-payment-modal__qr-image[hidden],
            .webai-payment-modal__qr-note[hidden] {
                display: none !important;
            }

            .webai-payment-modal__qr-note {
                max-width: 220px;
                color: #8d7c70;
                font-size: 11px;
                font-weight: 650;
                line-height: 1.45;
                text-align: center;
                order: 3;
            }

            .webai-payment-modal__close {
                position: absolute;
                z-index: 2;
                top: 12px;
                right: 13px;
                width: 34px;
                height: 34px;
                border: 0;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.84);
                color: #686a78;
                font-size: 28px;
                font-weight: 300;
                line-height: 1;
                cursor: pointer;
            }

            @media (max-width: 900px) {
                .webai-video-delete,
                .webai-generation-download {
                    opacity: 1;
                    transform: scale(1);
                }

                .webai-video-delete {
                    top: calc(50% - 29px);
                }

                .webai-generation-download {
                    top: calc(50% + 3px);
                }

                .webai-shell {
                    grid-template-columns: 1fr;
                }

                .webai-shell::after {
                    content: "";
                    position: fixed;
                    inset: 58px 0 0;
                    z-index: 998;
                    background: rgba(47, 47, 58, 0.22);
                    opacity: 0;
                    pointer-events: none;
                    transition: opacity 0.2s ease;
                }

                body.webai-menu-open .webai-shell::after {
                    opacity: 1;
                    pointer-events: auto;
                }

                .webai-mobile-header {
                    position: sticky;
                    top: 0;
                    z-index: 1000;
                    grid-column: 1 / -1;
                    height: 58px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 0 16px;
                    border-bottom: 1px solid rgba(198, 198, 210, 0.86);
                    background: rgba(255, 255, 255, 0.94);
                    box-shadow: 0 8px 22px rgba(72, 72, 90, 0.08);
                    backdrop-filter: blur(14px);
                }

                .webai-mobile-logo {
                    position: absolute;
                    left: 50%;
                    top: 50%;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    max-width: calc(100% - 128px);
                    color: var(--webai-text);
                    text-decoration: none;
                    transform: translate(-50%, -50%);
                }

                .webai-mobile-logo-image {
                    width: auto;
                    max-width: 142px;
                    max-height: 36px;
                    object-fit: contain;
                }

                .webai-mobile-logo-mark {
                    width: 34px;
                    height: 34px;
                    display: grid;
                    place-items: center;
                    border-radius: 8px;
                    background: rgba(255, 255, 255, 0.78);
                    color: #d6d6de;
                    font-size: 20px;
                    font-weight: 900;
                    box-shadow: 0 8px 18px rgba(95, 95, 115, 0.14);
                }

                .webai-mobile-logo-text {
                    font-size: 20px;
                    font-weight: 900;
                    white-space: nowrap;
                }

                .webai-mobile-menu-toggle,
                .webai-mobile-account {
                    width: 38px;
                    height: 38px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    border: 1px solid rgba(255, 122, 0, 0.82);
                    border-radius: 10px;
                    background: rgba(255, 255, 255, 0.86);
                    color: var(--webai-accent);
                    box-shadow: 0 4px 12px rgba(255, 122, 0, 0.13);
                }

                .webai-mobile-menu-toggle {
                    flex-direction: column;
                    gap: 5px;
                    padding: 0;
                    cursor: pointer;
                }

                .webai-mobile-menu-toggle span {
                    width: 18px;
                    height: 2px;
                    border-radius: 999px;
                    background: currentColor;
                    transition: transform 0.2s ease, opacity 0.2s ease;
                }

                body.webai-menu-open .webai-mobile-menu-toggle span:nth-child(1) {
                    transform: translateY(7px) rotate(45deg);
                }

                body.webai-menu-open .webai-mobile-menu-toggle span:nth-child(2) {
                    opacity: 0;
                }

                body.webai-menu-open .webai-mobile-menu-toggle span:nth-child(3) {
                    transform: translateY(-7px) rotate(-45deg);
                }

                .webai-mobile-account {
                    overflow: hidden;
                    text-decoration: none;
                }

                .webai-mobile-account img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    border-radius: inherit;
                }

                .webai-mobile-account span {
                    font-size: 15px;
                    font-weight: 900;
                }

                .webai-mobile-account svg {
                    width: 20px;
                    height: 20px;
                    fill: none;
                    stroke: currentColor;
                    stroke-width: 2;
                    stroke-linecap: round;
                    stroke-linejoin: round;
                }

                .webai-sidebar {
                    position: fixed;
                    top: 58px;
                    bottom: 0;
                    left: 0;
                    z-index: 999;
                    width: min(82vw, 300px);
                    height: calc(100vh - 58px);
                    padding: 18px 16px;
                    overflow-y: auto;
                    transform: translateX(-105%);
                    transition: transform 0.22s ease;
                }

                body.webai-menu-open .webai-sidebar {
                    transform: translateX(0);
                }

                .webai-brand {
                    padding-bottom: 18px;
                }

                .webai-nav {
                    grid-template-columns: 1fr;
                }

                .webai-nav-list {
                    grid-template-columns: 1fr;
                }

                .webai-nav-sub {
                    margin-left: 0;
                }

                .webai-user {
                    margin-top: 18px;
                    row-gap: 10px;
                }

                .webai-sidebar-promo {
                    margin-top: 12px;
                }

                .webai-logout {
                    position: static;
                    grid-column: 1 / -1;
                    width: 100%;
                    margin-top: 2px;
                    opacity: 1;
                    pointer-events: auto;
                    transform: none;
                }

                .webai-auth-actions {
                    margin-top: 18px;
                }

                .webai-main {
                    padding: 28px 16px 20px;
                }

                .webai-page-header {
                    align-items: flex-start;
                    flex-direction: column;
                    gap: 14px;
                }

                .webai-page-header__actions {
                    width: 100%;
                    gap: 12px;
                }

                .webai-page-header__profile {
                    margin-left: auto;
                }

                .webai-tool-page {
                    min-height: calc(100vh - 48px);
                    width: 100%;
                }

                .webai-composer {
                    width: 100%;
                }

                .webai-home-hero {
                    grid-template-columns: 1fr;
                }

                .webai-hero-copy {
                    padding: 32px 24px 0;
                }

                .webai-hero-visual {
                    min-height: 260px;
                }

                .webai-hero-visual svg {
                    right: 50%;
                    width: 330px;
                    transform: translateX(50%);
                }

                .webai-tool-grid {
                    grid-template-columns: 1fr;
                }

                .webai-content-posts__grid {
                    grid-template-columns: 1fr;
                }

                .webai-credit-page {
                    padding: 34px 18px;
                }

                .webai-pricing-grid {
                    grid-template-columns: 1fr;
                }

                .webai-buy-button {
                    opacity: 1;
                    visibility: visible;
                    transform: none;
                }

                .webai-auth-page {
                    min-height: calc(100vh - 86px);
                    padding: 18px 0 28px;
                }

                .webai-auth-shell {
                    grid-template-columns: 1fr;
                    gap: 16px;
                }

                .webai-auth-copy {
                    min-height: auto;
                    padding: 28px 24px;
                }

                .webai-auth-copy h1 {
                    max-width: none;
                    font-size: 32px;
                }

                .webai-auth-copy p {
                    max-width: none;
                    margin-top: 14px;
                    font-size: 16px;
                }

                .webai-auth-form .auth-card {
                    min-height: auto;
                }
            }

            @media (max-width: 560px) {
                .webai-nav {
                    grid-template-columns: 1fr;
                }

                .webai-nav-list {
                    grid-template-columns: 1fr;
                }

                .webai-title {
                    font-size: 34px;
                }

                .webai-subtitle {
                    font-size: 17px;
                }

                .webai-composer {
                    padding: 18px;
                    border-radius: 18px;
                }

                .webai-hero-copy h1 {
                    font-size: 32px;
                }

                .webai-hero-copy p {
                    font-size: 16px;
                }

                .webai-toolbar {
                    display: grid;
                    grid-template-columns: minmax(0, 1.6fr) minmax(44px, 0.7fr) minmax(44px, 0.7fr);
                    align-items: stretch;
                    gap: 8px;
                }

                .webai-filter-toggle,
                .webai-cost,
                .webai-send {
                    width: 100%;
                    min-height: 34px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                }

                .webai-filter-toggle {
                    order: 2;
                    position: relative;
                    justify-content: flex-start;
                    padding: 0 32px 0 12px;
                    border: 1px solid var(--webai-accent);
                    border-radius: 9px;
                    background: rgba(255, 255, 255, 0.82);
                    color: var(--webai-accent);
                    box-shadow: 0 3px 8px rgba(255, 122, 0, 0.16);
                    cursor: pointer;
                }

                .webai-filter-toggle__label {
                    overflow: hidden;
                    color: var(--webai-text);
                    font-size: 13px;
                    font-weight: 800;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                .webai-filter-toggle::after {
                    content: '';
                    position: absolute;
                    top: 50%;
                    right: 13px;
                    width: 7px;
                    height: 7px;
                    border-right: 2px solid var(--webai-accent);
                    border-bottom: 2px solid var(--webai-accent);
                    transform: translateY(-65%) rotate(45deg);
                    transition: transform 0.18s ease;
                }

                .webai-filter-toggle.is-active {
                    background: var(--webai-accent-soft);
                    border-color: var(--webai-accent);
                    color: var(--webai-accent);
                    box-shadow: 0 0 0 1px rgba(255, 122, 0, 0.2), 0 3px 8px rgba(255, 122, 0, 0.16);
                }

                .webai-filter-toggle.is-active::after {
                    transform: translateY(-35%) rotate(225deg);
                }

                .webai-filter-panel {
                    order: 1;
                    grid-column: 1 / -1;
                    display: none;
                    gap: 8px;
                    padding-bottom: 2px;
                }

                .webai-filter-panel.is-open {
                    display: grid;
                }

                .webai-filter-panel .webai-pill,
                .webai-filter-panel .webai-select,
                .webai-filter-panel .webai-model-picker {
                    width: 100%;
                    min-height: 34px;
                    justify-content: center;
                }

                .webai-filter-panel .webai-pill {
                    justify-content: center;
                }

                .webai-spacer {
                    display: none;
                }

                .webai-cost {
                    order: 3;
                    padding: 0 7px;
                    font-size: 12px;
                }

                .webai-send {
                    order: 4;
                }

                .webai-auth-copy {
                    padding: 24px 18px;
                }

                .webai-auth-copy h1 {
                    font-size: 28px;
                }

                .webai-auth-form .auth-card__header {
                    padding: 26px 20px 10px;
                }

                .webai-auth-form .auth-card__header > div {
                    align-items: flex-start !important;
                }

                .webai-auth-form .auth-card__header-title {
                    font-size: 22px !important;
                }

                .webai-auth-form .auth-card__body {
                    padding: 16px 20px 24px;
                }

                .webai-auth-form .row.g-0.mb-3 {
                    row-gap: 10px;
                }

                .webai-auth-form .row.g-0.mb-3 > [class*="col-"] {
                    width: 100%;
                    text-align: left !important;
                }
            }

            @media (max-width: 560px) {
                .webai-credit-payment-page {
                    width: min(100% - 28px, 900px);
                    margin-top: 24px;
                }

                .webai-credit-payment-card {
                    grid-template-columns: 1fr;
                }

                .webai-credit-payment-details,
                .webai-credit-payment-qr {
                    padding: 30px 24px;
                }

                .webai-event-modal {
                    padding: 16px;
                }

                .webai-event-modal__dialog {
                    min-height: 0;
                    grid-template-columns: 1fr;
                    grid-template-rows: 190px auto;
                }

                .webai-event-modal__content {
                    padding: 28px 24px 30px;
                }

                .webai-event-modal__content p {
                    margin: 12px 0 16px;
                }

                .webai-payment-modal {
                    padding: 16px;
                }

                .webai-payment-modal__dialog {
                    grid-template-columns: 1fr;
                }

                .webai-payment-modal__details,
                .webai-payment-modal__qr {
                    padding: 30px 24px;
                }

                .webai-payment-modal__qr {
                    padding-top: 20px;
                }
            }
        </style>
    </head>
    <body {!! Theme::bodyAttributes() !!}>
        {!! apply_filters(THEME_FRONT_BODY, null) !!}

        @yield('content')

        {!! dynamic_sidebar('webai_floating_contact_sidebar') !!}

        {!! Theme::footer() !!}

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const toggle = document.querySelector('[data-webai-menu-toggle]');
                const sidebar = document.querySelector('#webai-sidebar');
                const shell = document.querySelector('.webai-shell');
                const userMenu = document.querySelector('[data-webai-user-menu]');

                if (userMenu) {
                    const closeUserMenu = function () {
                        userMenu.classList.remove('is-logout-open');
                        userMenu.setAttribute('aria-expanded', 'false');
                    };

                    const toggleUserMenu = function () {
                        const isOpen = userMenu.classList.toggle('is-logout-open');
                        userMenu.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    };

                    userMenu.addEventListener('click', function (event) {
                        if (!event.target.closest('.webai-logout')) {
                            toggleUserMenu();
                        }
                    });

                    userMenu.addEventListener('keydown', function (event) {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            toggleUserMenu();
                        }
                    });

                    document.addEventListener('click', function (event) {
                        if (!userMenu.contains(event.target)) {
                            closeUserMenu();
                        }
                    });

                    document.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape') {
                            closeUserMenu();
                        }
                    });
                }

                if (!toggle || !sidebar || !shell) {
                    return;
                }

                const closeMenu = function () {
                    document.body.classList.remove('webai-menu-open');
                    toggle.setAttribute('aria-expanded', 'false');
                };

                toggle.addEventListener('click', function () {
                    const isOpen = document.body.classList.toggle('webai-menu-open');
                    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                });

                document.addEventListener('click', function (event) {
                    if (!document.body.classList.contains('webai-menu-open')) {
                        return;
                    }

                    if (event.target.closest('#webai-sidebar') || event.target.closest('[data-webai-menu-toggle]')) {
                        return;
                    }

                    closeMenu();
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        closeMenu();
                    }
                });

                sidebar.querySelectorAll('a').forEach(function (link) {
                    link.addEventListener('click', closeMenu);
                });

                document.querySelectorAll('.webai-composer').forEach(function (composer) {
                    const filterToggle = composer.querySelector('[data-webai-filter-toggle]');
                    const filterPanel = composer.querySelector('[data-webai-filter-panel]');
                    const modelSelect = composer.querySelector('.webai-model-select');
                    const mediaFields = composer.querySelector('[data-webai-media-fields]');
                    const mediaFieldItems = composer.querySelectorAll('[data-webai-media-field]');
                    const mediaUploadUrl = composer.dataset.webaiMediaUploadUrl;
                    const taskStatusUrl = composer.dataset.webaiTaskStatusUrl;
                    const taskHistoryUrl = composer.dataset.webaiTaskHistoryUrl;
                    const taskDownloadUrl = composer.dataset.webaiTaskDownloadUrl;
                    const taskDeleteUrl = composer.dataset.webaiTaskDeleteUrl;
                    const modelPicker = composer.querySelector('[data-webai-model-picker]');
                    const modelPickerToggle = modelPicker ? modelPicker.querySelector('[data-webai-model-picker-toggle]') : null;
                    const modelPickerMenu = modelPicker ? modelPicker.querySelector('[data-webai-model-picker-menu]') : null;
                    const modelPickerParent = modelPicker ? modelPicker.querySelector('[data-webai-model-picker-parent]') : null;
                    const modelPickerLabel = modelPicker ? modelPicker.querySelector('[data-webai-model-picker-label]') : null;
                    const mobileModelLabel = composer.querySelector('[data-webai-mobile-model-label]');
                    const toolPage = composer.closest('.webai-tool-page');
                    const recentPanel = toolPage?.querySelector('[data-webai-recent-panel]');
                    const recentList = recentPanel ? recentPanel.querySelector('[data-webai-recent-list]') : null;
                    const videoGallery = toolPage?.classList.contains('webai-video-tool-page');
                    const generationPanel = videoGallery ? recentPanel : toolPage?.querySelector('[data-webai-generation-panel]');
                    const generationList = videoGallery ? recentList : (generationPanel ? generationPanel.querySelector('[data-webai-generation-list]') : null);
                    const qualitySelect = composer.querySelector('[data-webai-quality-select]');
                    const durationSelect = composer.querySelector('[data-webai-duration-select]');
                    const aspectRatioSelect = composer.querySelector('[data-webai-aspect-ratio-select]');
                    const characterOrientationSelect = composer.querySelector('[data-webai-character-orientation-select]');
                    const shotTypeSelect = composer.querySelector('[data-webai-shot-type-select]');
                    const costElement = composer.querySelector('[data-webai-cost]');
                    const submitButton = composer.querySelector('.webai-send');
                    const creditBalanceElement = document.querySelector('[data-webai-credit-balance]');
                    const deleteModal = toolPage?.querySelector('[data-webai-video-delete-modal]');
                    const deleteConfirmButton = deleteModal?.querySelector('[data-webai-video-delete-confirm]');
                    const loadMoreTrigger = toolPage?.querySelector('[data-webai-video-load-more]');
                    let pendingMediaUploads = 0;
                    let uploadedVideoDuration = null;
                    let pendingDeleteCard = null;
                    let nextHistoryCursor = loadMoreTrigger?.dataset.webaiNextCursor || '';
                    let isLoadingHistory = false;

                    const syncSubmitState = function () {
                        if (submitButton) {
                            submitButton.disabled = pendingMediaUploads > 0;
                        }
                    };

                    if (!filterToggle || !filterPanel) {
                        return;
                    }

                    filterToggle.addEventListener('click', function () {
                        const isOpen = filterPanel.classList.toggle('is-open');
                        filterToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                        filterToggle.classList.toggle('is-active', isOpen);
                    });

                    const readOptions = function (selectedOption, key) {
                        try {
                            return JSON.parse(selectedOption.dataset[key] || '[]');
                        } catch (error) {
                            return [];
                        }
                    };

                    const syncSelect = function (select, options) {
                        if (!select) {
                            return;
                        }

                        select.innerHTML = '';

                        if (!options.length) {
                            select.hidden = true;

                            return;
                        }

                        select.hidden = false;

                        options.forEach(function (item) {
                            const option = document.createElement('option');
                            option.value = item.value;
                            option.textContent = item.label;
                            select.appendChild(option);
                        });
                    };

                    const syncCost = function () {
                        if (!costElement || !modelSelect) {
                            return;
                        }

                        const selectedOption = modelSelect.options[modelSelect.selectedIndex];
                        const price = Number(selectedOption.dataset.price || 0);
                        const usesUploadedVideo = readOptions(selectedOption, 'fields').includes('video_url');
                        const duration = usesUploadedVideo && uploadedVideoDuration
                            ? uploadedVideoDuration
                            : (durationSelect && !durationSelect.hidden ? Number(durationSelect.value || 1) : 1);
                        const cost = price * duration;

                        costElement.textContent = String(Math.ceil(cost));
                    };

                    const readVideoDuration = function (file) {
                        return new Promise(function (resolve, reject) {
                            const video = document.createElement('video');
                            const objectUrl = URL.createObjectURL(file);

                            video.preload = 'metadata';
                            video.onloadedmetadata = function () {
                                const duration = video.duration;
                                URL.revokeObjectURL(objectUrl);
                                Number.isFinite(duration) ? resolve(duration) : reject(new Error('Không thể đọc thời lượng video.'));
                            };
                            video.onerror = function () {
                                URL.revokeObjectURL(objectUrl);
                                reject(new Error('Không thể đọc thời lượng video.'));
                            };
                            video.src = objectUrl;
                        });
                    };

                    const syncMediaFields = function (fields, requiredFields) {
                        if (!mediaFields || !mediaFieldItems.length) {
                            return;
                        }

                        let hasVisibleField = false;

                        mediaFieldItems.forEach(function (field) {
                            const fieldName = field.dataset.webaiMediaField;
                            const input = field.querySelector('[data-webai-media-url]');
                            const preview = field.querySelector('[data-webai-media-preview]');
                            const clearButton = field.querySelector('[data-webai-media-clear]');
                            const requiredMark = field.querySelector('strong');
                            const isVisible = fields.includes(fieldName);
                            const isRequired = requiredFields.includes(fieldName);

                            field.hidden = !isVisible;
                            hasVisibleField = hasVisibleField || isVisible;

                            if (!input) {
                                return;
                            }

                            input.required = isRequired;

                            if (!isVisible) {
                                input.value = '';

                                if (preview) {
                                    preview.textContent = 'Chưa chọn';
                                }

                                if (clearButton) {
                                    clearButton.hidden = true;
                                }
                            }

                            if (requiredMark) {
                                requiredMark.hidden = !isRequired;
                            }
                        });

                        mediaFields.hidden = !hasVisibleField;
                    };

                    const setMediaValue = function (button, url) {
                        const field = button.closest('[data-webai-media-field]');

                        if (!field) {
                            return;
                        }

                        const input = field.querySelector('[data-webai-media-url]');
                        const preview = field.querySelector('[data-webai-media-preview]');
                        const imagePreview = field.querySelector('[data-webai-image-preview]');
                        const videoPreview = field.querySelector('[data-webai-video-preview]');
                        const clearButton = field.querySelector('[data-webai-media-clear]');

                        if (input) {
                            input.value = url || '';
                        }

                        if (field.dataset.webaiMediaField === 'video_url' && !url) {
                            uploadedVideoDuration = null;
                            syncCost();
                        }

                        if (preview) {
                            preview.textContent = url || 'Chưa chọn';
                        }

                        if (imagePreview) {
                            imagePreview.hidden = !url;
                            imagePreview.src = url || '';
                        }

                        if (videoPreview) {
                            videoPreview.hidden = !url;
                            videoPreview.src = url || '';
                        }

                        if (clearButton) {
                            clearButton.hidden = !url;
                        }
                    };

                    const resetComposerAfterGeneration = function () {
                        const prompt = composer.querySelector('.webai-prompt');

                        if (prompt) {
                            prompt.value = '';
                        }

                        mediaFieldItems.forEach(function (field) {
                            const pickButton = field.querySelector('[data-webai-media-pick]');
                            const fileInput = field.querySelector('[data-webai-media-file]');

                            if (pickButton) {
                                setMediaValue(pickButton, '');
                            }

                            if (fileInput) {
                                fileInput.value = '';
                            }
                        });
                    };

                    const syncVideoCardOrientation = function (video) {
                        const card = video.closest('.webai-recent-item, .webai-generation-card');
                        const gallery = card ? card.parentElement : null;

                        if (!card || !gallery || !video.videoWidth || !video.videoHeight) {
                            return;
                        }

                        const isPortrait = video.videoHeight > video.videoWidth;
                        const galleryStyle = window.getComputedStyle(gallery);
                        const rowHeight = parseFloat(galleryStyle.gridAutoRows);
                        const rowGap = parseFloat(galleryStyle.rowGap) || 0;
                        const targetHeight = card.getBoundingClientRect().width * (video.videoHeight / video.videoWidth);
                        const rowSpan = Number.isFinite(rowHeight) && rowHeight > 0
                            ? Math.max(1, Math.round((targetHeight + rowGap) / (rowHeight + rowGap)))
                            : (isPortrait ? 2 : 1);

                        card.classList.toggle('webai-media-item--portrait', isPortrait);
                        card.style.gridRow = 'span ' + rowSpan;
                        card.style.aspectRatio = video.videoWidth + ' / ' + video.videoHeight;
                        video.style.objectFit = 'cover';
                        video.style.backgroundColor = '#000';
                    };

                    const watchVideoCardOrientation = function (video) {
                        if (video.readyState >= HTMLMediaElement.HAVE_METADATA) {
                            syncVideoCardOrientation(video);

                            return;
                        }

                        video.addEventListener('loadedmetadata', function () {
                            syncVideoCardOrientation(video);
                        }, { once: true });

                        video.addEventListener('loadeddata', function () {
                            syncVideoCardOrientation(video);
                        }, { once: true });
                    };

                    const terminalStatuses = ['COMPLETED', 'FAILED', 'ERROR', 'CANCELED', 'CANCELLED'];

                    const normalizeGeneratedMedia = function (generated) {
                        return (Array.isArray(generated) ? generated : [])
                            .map(function (item) {
                                if (typeof item === 'string' && item) {
                                    return { url: item, thumbnail: '' };
                                }

                                if (!item || typeof item.url !== 'string' || !item.url) {
                                    return null;
                                }

                                return {
                                    url: item.url,
                                    thumbnail: typeof item.thumbnail === 'string' ? item.thumbnail : '',
                                };
                            })
                            .filter(Boolean);
                    };

                    const generationTitle = function (taskId) {
                        return 'Video ' + (taskId ? String(taskId).slice(-6) : Date.now());
                    };

                    const ensureVideoDeleteButton = function (card) {
                        if (!videoGallery || !card || !card.dataset.webaiTaskId || card.querySelector('[data-webai-video-delete]')) {
                            return;
                        }

                        const button = document.createElement('button');
                        button.className = 'webai-video-delete';
                        button.type = 'button';
                        button.dataset.webaiVideoDelete = '';
                        button.setAttribute('aria-label', 'Xóa video');
                        button.textContent = '×';
                        card.prepend(button);
                    };

                    const showGeneratedPreview = function (generated, title) {
                        if (!generationPanel || !generationList) {
                            return;
                        }

                        generationList.innerHTML = '';
                        generationPanel.hidden = false;

                        const card = document.createElement('article');
                        card.className = 'webai-generation-card is-completed';
                        card.dataset.webaiSource = 'preview';

                        const status = document.createElement('p');
                        status.className = 'webai-generation-status';
                        status.textContent = title || 'Video';

                        const media = document.createElement('div');
                        media.className = 'webai-generation-media';

                        card.appendChild(status);
                        card.appendChild(media);
                        generationList.appendChild(card);
                        renderGeneratedMedia(card, generated || []);
                    };

                    const moveCurrentGenerationToRecent = function () {
                        if (videoGallery) {
                            return;
                        }

                        if (!generationList || !recentList) {
                            return;
                        }

                        generationList.querySelectorAll('.webai-generation-card').forEach(function (card) {
                            if (card.dataset.webaiSource !== 'new') {
                                card.remove();

                                return;
                            }

                            const mediaElement = card.querySelector('.webai-generation-media video, .webai-generation-media img');
                            const mediaUrls = Array.from(card.querySelectorAll('.webai-generation-media video, .webai-generation-media img'))
                                .map(function (element) {
                                    return element.currentSrc || element.src;
                                })
                                .filter(Boolean);

                            if (!mediaElement || !mediaUrls.length) {
                                card.remove();

                                return;
                            }

                            const item = document.createElement('div');
                            item.className = 'webai-recent-item';
                            item.dataset.webaiGenerated = JSON.stringify(mediaUrls);
                            item.dataset.webaiTitle = card.dataset.webaiTitle || generationTitle(card.dataset.webaiTaskId);
                            item.title = item.dataset.webaiTitle;

                            const clone = mediaElement.cloneNode(true);

                            if (clone.tagName.toLowerCase() === 'video') {
                                clone.controls = false;
                                clone.muted = true;
                                clone.playsInline = true;
                            }

                            item.appendChild(clone);
                            recentList.prepend(item);
                            card.remove();
                        });

                        if (recentPanel && recentList.children.length) {
                            recentPanel.classList.add('has-items');
                        }
                    };

                    const createGenerationCard = function (taskId) {
                        if (!generationPanel || !generationList) {
                            return null;
                        }

                        moveCurrentGenerationToRecent();
                        generationPanel.hidden = false;

                        const card = document.createElement('article');
                        card.className = 'webai-generation-card is-loading';
                        card.dataset.webaiTaskId = taskId;
                        card.dataset.webaiSource = 'new';

                        const status = document.createElement('p');
                        status.className = 'webai-generation-status';
                        status.textContent = '\u0110ang t\u1ea1o video';

                        const media = document.createElement('div');
                        media.className = 'webai-generation-media';

                        card.appendChild(status);
                        card.appendChild(media);
                        generationList.prepend(card);

                        return card;
                    };

                    const renderGeneratedMedia = function (card, generated) {
                        const media = card.querySelector('.webai-generation-media');

                        if (!media) {
                            return;
                        }

                        media.innerHTML = '';
                        card.querySelector('.webai-generation-download')?.remove();

                        if (!generated || !generated.length) {
                            const empty = document.createElement('p');
                            empty.textContent = 'Ch\u01b0a c\u00f3 file k\u1ebft qu\u1ea3.';
                            media.appendChild(empty);

                            return;
                        }

                        normalizeGeneratedMedia(generated).forEach(function (generatedMedia, mediaIndex) {
                            const url = generatedMedia.url;

                            const extension = url.split('?')[0].split('.').pop().toLowerCase();
                            const element = ['mp4', 'mov', 'webm'].includes(extension)
                                ? document.createElement('video')
                                : document.createElement('img');

                            if (element.tagName.toLowerCase() === 'video') {
                                element.controls = true;
                                element.playsInline = true;
                                element.poster = generatedMedia.thumbnail;

                                if (!card.querySelector('.webai-generation-download')) {
                                    const download = document.createElement('a');
                                    download.className = 'webai-generation-download';
                                    download.href = card.dataset.webaiTaskId && taskDownloadUrl
                                        ? taskDownloadUrl.replace('__TASK_ID__', encodeURIComponent(card.dataset.webaiTaskId)) + '?media=' + mediaIndex
                                        : url;
                                    download.setAttribute('aria-label', 'Tải video về máy');
                                    download.title = 'Tải video';
                                    download.textContent = '↓ Tải video';
                                    card.appendChild(download);
                                }
                            }

                            element.src = url;
                            media.appendChild(element);

                            if (element.tagName.toLowerCase() === 'video') {
                                watchVideoCardOrientation(element);
                            }
                        });
                    };

                    const appendVideoHistory = function (tasks) {
                        if (!videoGallery || !recentList || !Array.isArray(tasks)) {
                            return;
                        }

                        tasks.forEach(function (task) {
                            const taskId = task?.task_id;
                            const generated = normalizeGeneratedMedia(task?.generated);
                            const firstMedia = generated[0];
                            const firstMediaUrl = firstMedia?.url;
                            const exists = Array.from(recentList.querySelectorAll('[data-webai-task-id]')).some(function (card) {
                                return card.dataset.webaiTaskId === String(taskId);
                            });

                            if (!taskId || !firstMediaUrl || exists) {
                                return;
                            }

                            const item = document.createElement('div');
                            item.className = 'webai-recent-item';
                            item.dataset.webaiTaskId = taskId;
                            item.dataset.webaiGenerated = JSON.stringify(generated);
                            item.dataset.webaiTitle = generationTitle(taskId);
                            item.title = item.dataset.webaiTitle;

                            const extension = firstMediaUrl.split('?')[0].split('.').pop().toLowerCase();
                            const media = ['mp4', 'mov', 'webm'].includes(extension)
                                ? document.createElement('video')
                                : document.createElement('img');

                            if (media.tagName.toLowerCase() === 'video') {
                                media.controls = true;
                                media.playsInline = true;
                                media.poster = firstMedia.thumbnail;
                            } else {
                                media.alt = item.dataset.webaiTitle;
                            }

                            media.src = firstMediaUrl;
                            item.appendChild(media);

                            if (media.tagName.toLowerCase() === 'video') {
                                const download = document.createElement('a');
                                download.className = 'webai-generation-download';
                                download.href = taskDownloadUrl
                                    ? taskDownloadUrl.replace('__TASK_ID__', encodeURIComponent(taskId)) + '?media=0'
                                    : firstMediaUrl;
                                download.setAttribute('aria-label', 'Tải video về máy');
                                download.title = 'Tải video';
                                download.textContent = '↓ Tải video';
                                item.appendChild(download);
                            }

                            ensureVideoDeleteButton(item);
                            recentList.appendChild(item);

                            if (media.tagName.toLowerCase() === 'video') {
                                watchVideoCardOrientation(media);
                            }
                        });

                        if (recentList.children.length) {
                            recentPanel?.classList.add('has-items');
                        }
                    };

                    const loadMoreVideoHistory = function () {
                        if (!videoGallery || !taskHistoryUrl || !nextHistoryCursor || isLoadingHistory) {
                            return;
                        }

                        isLoadingHistory = true;

                        const url = new URL(taskHistoryUrl, window.location.origin);
                        url.searchParams.set('cursor', nextHistoryCursor);

                        fetch(url, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                            },
                        })
                            .then(function (response) {
                                return response.json();
                            })
                            .then(function (response) {
                                if (!response.success || !response.data) {
                                    throw new Error('Cannot load video history');
                                }

                                appendVideoHistory(response.data.tasks || []);
                                nextHistoryCursor = response.data.next_cursor || '';

                                if (loadMoreTrigger) {
                                    loadMoreTrigger.dataset.webaiNextCursor = nextHistoryCursor;
                                    loadMoreTrigger.hidden = !nextHistoryCursor;
                                }
                            })
                            .catch(function () {
                            })
                            .finally(function () {
                                isLoadingHistory = false;
                            });
                    };

                    const renderTaskStatus = function (card, task) {
                        const statusText = (task.status || '').toUpperCase();
                        const status = card.querySelector('.webai-generation-status');

                        if (status) {
                            status.textContent = task.is_completed ? generationTitle(task.task_id) : '\u0110ang t\u1ea1o video';
                        }

                        if (task.is_completed) {
                            card.dataset.webaiTitle = generationTitle(task.task_id);
                            card.classList.remove('is-loading', 'is-failed');
                            card.classList.add('is-completed');
                            renderGeneratedMedia(card, task.generated || []);
                            ensureVideoDeleteButton(card);
                            resetComposerAfterGeneration();

                            return true;
                        }

                        if (terminalStatuses.includes(statusText)) {
                            card.classList.remove('is-loading', 'is-completed');
                            card.classList.add('is-failed');

                            if (status) {
                                status.textContent = 'T\u1ea1o video th\u1ea5t b\u1ea1i';
                            }

                            return true;
                        }

                        return false;
                    };

                    const pollTask = function (taskId, card) {
                        if (!taskStatusUrl || !card) {
                            return;
                        }

                        const url = taskStatusUrl.replace('__TASK_ID__', encodeURIComponent(taskId));
                        const timer = window.setInterval(function () {
                            fetch(url, {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                },
                            })
                                .then(function (response) {
                                    return response.json();
                                })
                                .then(function (response) {
                                    if (!response.success || !response.data) {
                                        return;
                                    }

                                    if (renderTaskStatus(card, response.data)) {
                                        window.clearInterval(timer);
                                    }
                                })
                                .catch(function () {
                                });
                        }, 3000);
                    };

                    composer.addEventListener('submit', function (event) {
                        event.preventDefault();

                        if (composer.dataset.webaiIsAuthenticated !== 'true') {
                            window.location.assign(composer.dataset.webaiLoginUrl);
                            return;
                        }

                        const formData = new FormData(composer);

                        if (pendingMediaUploads > 0) {
                            return;
                        }

                        if (submitButton) {
                            submitButton.disabled = true;
                            submitButton.classList.add('is-loading');
                            submitButton.setAttribute('aria-busy', 'true');
                        }

                        fetch(composer.action, {
                            method: composer.method || 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: formData,
                        })
                            .then(function (response) {
                                if (response.status === 401) {
                                    window.location.assign(composer.dataset.webaiLoginUrl);
                                    return null;
                                }

                                return response.json();
                            })
                            .then(function (response) {
                                if (!response) {
                                    return;
                                }

                                const task = response.data || {};

                                if (!response.success || !task.task_id) {
                                    throw new Error(response.message || 'Cannot create task');
                                }

                                if (creditBalanceElement && Number.isFinite(Number(task.credits_balance))) {
                                    creditBalanceElement.textContent = 'Credits: ' + new Intl.NumberFormat('vi-VN').format(Number(task.credits_balance));
                                }

                                const card = createGenerationCard(task.task_id);

                                if (card) {
                                    if (!renderTaskStatus(card, task)) {
                                        pollTask(task.task_id, card);
                                    }
                                }
                            })
                            .catch(function (error) {
                                alert(error.message || 'Kh\u00f4ng g\u1eedi \u0111\u01b0\u1ee3c y\u00eau c\u1ea7u t\u1ea1o video. Vui l\u00f2ng th\u1eed l\u1ea1i.');
                            })
                            .finally(function () {
                                if (submitButton) {
                                    submitButton.disabled = false;
                                    submitButton.classList.remove('is-loading');
                                    submitButton.removeAttribute('aria-busy');
                                }
                            });
                    });

                    if (recentList && !videoGallery) {
                        recentList.addEventListener('click', function (event) {
                            const item = event.target.closest('.webai-recent-item');

                            if (!item) {
                                return;
                            }

                            try {
                                moveCurrentGenerationToRecent();
                                showGeneratedPreview(JSON.parse(item.dataset.webaiGenerated || '[]'), item.dataset.webaiTitle || 'Video');
                            } catch (error) {
                            }
                        });
                    }

                    if (videoGallery && recentList) {
                        const syncGalleryVideoOrientation = function () {
                            recentList.querySelectorAll('video').forEach(function (video) {
                                watchVideoCardOrientation(video);
                                syncVideoCardOrientation(video);
                            });
                        };

                        syncGalleryVideoOrientation();
                        window.addEventListener('resize', function () {
                            window.requestAnimationFrame(syncGalleryVideoOrientation);
                        });
                    }

                    if (videoGallery && loadMoreTrigger && nextHistoryCursor && 'IntersectionObserver' in window) {
                        const historyObserver = new IntersectionObserver(function (entries) {
                            if (entries.some(function (entry) { return entry.isIntersecting; })) {
                                loadMoreVideoHistory();
                            }
                        }, { rootMargin: '360px 0px' });

                        historyObserver.observe(loadMoreTrigger);
                    }

                    if (videoGallery && recentList && deleteModal && deleteConfirmButton) {
                        const closeDeleteModal = function () {
                            pendingDeleteCard = null;
                            deleteModal.hidden = true;
                            deleteModal.setAttribute('aria-hidden', 'true');
                        };

                        const openDeleteModal = function (card) {
                            pendingDeleteCard = card;
                            deleteModal.hidden = false;
                            deleteModal.setAttribute('aria-hidden', 'false');
                            deleteConfirmButton.focus();
                        };

                        recentList.addEventListener('click', function (event) {
                            const button = event.target.closest('[data-webai-video-delete]');

                            if (!button) {
                                return;
                            }

                            event.preventDefault();
                            event.stopPropagation();

                            const card = button.closest('.webai-recent-item, .webai-generation-card');

                            if (card?.dataset.webaiTaskId) {
                                openDeleteModal(card);
                            }
                        });

                        deleteModal.querySelectorAll('[data-webai-video-delete-close]').forEach(function (button) {
                            button.addEventListener('click', closeDeleteModal);
                        });

                        deleteConfirmButton.addEventListener('click', function () {
                            const taskId = pendingDeleteCard?.dataset.webaiTaskId;

                            if (!taskId || !taskDeleteUrl) {
                                closeDeleteModal();

                                return;
                            }

                            deleteConfirmButton.disabled = true;
                            deleteConfirmButton.textContent = 'Đang xóa';

                            fetch(taskDeleteUrl.replace('__TASK_ID__', encodeURIComponent(taskId)), {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                },
                            })
                                .then(function (response) {
                                    return response.json();
                                })
                                .then(function (response) {
                                    if (!response.success) {
                                        throw new Error(response.message || 'Không thể xóa video.');
                                    }

                                    pendingDeleteCard?.remove();

                                    if (recentList.children.length === 0) {
                                        recentPanel?.classList.remove('has-items');
                                    }

                                    closeDeleteModal();
                                })
                                .catch(function (error) {
                                    alert(error.message || 'Không thể xóa video. Vui lòng thử lại.');
                                })
                                .finally(function () {
                                    deleteConfirmButton.disabled = false;
                                    deleteConfirmButton.textContent = 'Xóa video';
                                });
                        });
                    }

                    composer.querySelectorAll('[data-webai-media-clear]').forEach(function (button) {
                        button.addEventListener('click', function () {
                            setMediaValue(button, '');
                        });
                    });

                    composer.querySelectorAll('[data-webai-media-field="video_url"] [data-webai-media-pick]').forEach(function (button) {
                        button.setAttribute(
                            'data-webai-tooltip',
                            'Video phải có URL công khai. Thời lượng: 3-30 giây. Định dạng hỗ trợ: MP4, MOV, WEBM, M4V.'
                        );
                    });

                    composer.querySelectorAll('[data-webai-media-pick]').forEach(function (button) {
                        const field = button.closest('[data-webai-media-field]');
                        const fileInput = field ? field.querySelector('[data-webai-media-file]') : null;

                        if (!fileInput) {
                            return;
                        }

                        button.addEventListener('click', function () {
                            fileInput.click();
                        });

                        fileInput.addEventListener('change', async function () {
                            const file = fileInput.files && fileInput.files.length ? fileInput.files[0] : null;

                            if (!file) {
                                return;
                            }

                            if (field.dataset.webaiMediaField === 'video_url') {
                                try {
                                    const duration = await readVideoDuration(file);

                                    if (duration < 3 || duration > 30) {
                                        throw new Error('Video phải có thời lượng từ 3 đến 30 giây.');
                                    }

                                    uploadedVideoDuration = duration;
                                    syncCost();
                                } catch (error) {
                                    uploadedVideoDuration = null;
                                    syncCost();
                                    fileInput.value = '';
                                    alert(error.message || 'Không thể kiểm tra thời lượng video.');
                                    return;
                                }
                            }

                            const formData = new FormData();
                            formData.append('file', file);
                            formData.append('field', field.dataset.webaiMediaField || '');

                            pendingMediaUploads++;
                            syncSubmitState();
                            button.disabled = true;
                            button.textContent = 'Đang tải';

                            fetch(mediaUploadUrl, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                },
                                body: formData,
                            })
                                .then(function (response) {
                                    return response.json();
                                })
                                .then(function (response) {
                                    if (!response.success || !response.data || !response.data.url) {
                                        throw new Error(response.message || 'Upload failed');
                                    }

                                    setMediaValue(button, response.data.url);
                                })
                                .catch(function (error) {
                                    alert(error.message || 'Không tải được file. Vui lòng thử lại.');
                                })
                                .finally(function () {
                                    pendingMediaUploads = Math.max(0, pendingMediaUploads - 1);
                                    syncSubmitState();
                                    button.disabled = false;
                                    button.textContent = 'Thêm';
                                    fileInput.value = '';
                                });

                        });
                    });

                    if (modelSelect) {
                        const syncModelPicker = function () {
                            if (!modelPicker || !modelPickerParent || !modelPickerLabel) {
                                return;
                            }

                            const selectedOption = modelSelect.options[modelSelect.selectedIndex];
                            modelPickerParent.textContent = selectedOption.dataset.parent || 'Model AI';
                            modelPickerLabel.textContent = selectedOption.dataset.label || selectedOption.textContent.trim();

                            if (mobileModelLabel) {
                                mobileModelLabel.textContent = selectedOption.dataset.label || selectedOption.textContent.trim();
                            }

                            modelPicker.querySelectorAll('[data-webai-model-value]').forEach(function (button) {
                                button.classList.toggle('is-selected', button.dataset.webaiModelValue === selectedOption.value);
                            });
                        };

                        if (modelPicker && modelPickerToggle && modelPickerMenu) {
                            modelPickerToggle.addEventListener('click', function () {
                                const isOpen = !modelPickerMenu.hidden;
                                modelPickerMenu.hidden = isOpen;
                                modelPicker.classList.toggle('is-open', !isOpen);
                                modelPickerToggle.setAttribute('aria-expanded', String(!isOpen));
                            });

                            modelPicker.querySelectorAll('[data-webai-model-value]').forEach(function (button) {
                                button.addEventListener('click', function () {
                                    const option = Array.from(modelSelect.options).find(function (item) {
                                        return item.value === button.dataset.webaiModelValue;
                                    });

                                    if (!option) {
                                        return;
                                    }

                                    modelSelect.value = option.value;
                                    modelSelect.dispatchEvent(new Event('change'));
                                    modelPickerMenu.hidden = true;
                                    modelPicker.classList.remove('is-open');
                                    modelPickerToggle.setAttribute('aria-expanded', 'false');
                                });
                            });

                            document.addEventListener('click', function (event) {
                                if (!modelPicker.contains(event.target)) {
                                    modelPickerMenu.hidden = true;
                                    modelPicker.classList.remove('is-open');
                                    modelPickerToggle.setAttribute('aria-expanded', 'false');
                                }
                            });

                            syncModelPicker();
                        }

                        modelSelect.addEventListener('change', function () {
                            const selectedOption = modelSelect.options[modelSelect.selectedIndex];

                            syncModelPicker();

                            syncMediaFields(
                                readOptions(selectedOption, 'fields'),
                                readOptions(selectedOption, 'requiredFields')
                            );
                            syncSelect(qualitySelect, readOptions(selectedOption, 'qualities'));
                            syncSelect(durationSelect, readOptions(selectedOption, 'durations'));
                            syncSelect(aspectRatioSelect, readOptions(selectedOption, 'aspectRatios'));
                            syncSelect(characterOrientationSelect, readOptions(selectedOption, 'characterOrientations'));
                            syncSelect(shotTypeSelect, readOptions(selectedOption, 'shotTypes'));
                            syncCost();
                        });

                        if (durationSelect) {
                            durationSelect.addEventListener('change', syncCost);
                        }

                        syncCost();
                    }
                });
            });
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.querySelector('[data-webai-event-modal]');

                if (!modal) {
                    return;
                }

                modal.hidden = false;
                modal.setAttribute('aria-hidden', 'false');

                modal.querySelectorAll('[data-webai-event-close]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        modal.hidden = true;
                        modal.setAttribute('aria-hidden', 'true');
                    });
                });
            });
        </script>
    </body>
</html>
