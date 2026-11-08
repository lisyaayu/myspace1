```css
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --text: #51434b;
    --muted: #92818a;
    --pink: #f7dce5;
    --deep-pink: #c77d99;
    --cream: #fffaf7;
    --white: #ffffff;
}

html {
    scroll-behavior: smooth;
}

body {
    font-family: 'DM Sans', sans-serif;
    background: #fffaf9;
    color: var(--text);
}

body.modal-open {
    overflow: hidden;
}

.love-page {
    max-width: 1440px;
    margin: auto;
    padding: 0 7%;
}

.love-header {
    min-height: 85px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.back-link {
    text-decoration: none;
    color: #8d7580;
    font-size: 13px;
    font-weight: 600;
    transition: .2s;
}

.back-link:hover {
    color: #c77d99;
}

.back-link span {
    margin-right: 7px;
    font-size: 18px;
}

.header-label {
    font-size: 10px;
    letter-spacing: 2px;
    color: #b49ca6;
    font-weight: 700;
}

.love-hero {
    min-height: 420px;
    background: linear-gradient(115deg, #f9e5eb, #f9edf0 55%, #f3e8f3);
    border-radius: 28px;
    padding: 65px 8%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
}

.hero-content {
    max-width: 570px;
    position: relative;
    z-index: 2;
}

.hero-tag,
.section-eyebrow {
    display: inline-block;
    font-size: 10px;
    letter-spacing: 2px;
    font-weight: 700;
    color: #bd8299;
}

.hero-content h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(38px, 5vw, 62px);
    line-height: 1.13;
    font-weight: 600;
    color: #66515c;
    margin: 20px 0;
}

.hero-content h1 span {
    color: #bd8299;
    font-style: italic;
}

.hero-content p {
    max-width: 480px;
    color: #897782;
    font-size: 14px;
    line-height: 1.9;
    margin-bottom: 27px;
}

.hero-button {
    display: inline-flex;
    align-items: center;
    gap: 20px;
    background: #bd8299;
    color: white;
    text-decoration: none;
    padding: 13px 21px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 600;
    transition: .25s;
}

.hero-button:hover {
    background: #a96d85;
    transform: translateY(-3px);
}

.hero-button span {
    font-size: 17px;
}

.hero-illustration {
    width: 260px;
    height: 260px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 5%;
}

.hero-circle {
    width: 215px;
    height: 215px;
    border-radius: 50%;
    background: rgba(255,255,255,.5);
    border: 1px solid rgba(255,255,255,.8);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    box-shadow: 0 15px 50px rgba(174, 121, 145, .12);
}

.hero-heart {
    font-size: 105px;
    color: #cf8ca5;
    font-family: serif;
}

.hero-sparkle {
    position: absolute;
    color: #c58ba1;
}

.sparkle-one {
    top: 25px;
    right: 25px;
    font-size: 28px;
}

.sparkle-two {
    bottom: 25px;
    left: 25px;
    font-size: 20px;
}

.hero-small-text {
    position: absolute;
    right: -15px;
    bottom: -5px;
    color: #aa8494;
    font-family: 'Playfair Display', serif;
    font-style: italic;
    font-size: 13px;
    line-height: 1.5;
}

.hero-decoration {
    position: absolute;
    color: rgba(190, 128, 153, .3);
    font-family: serif;
}

.decoration-one {
    top: 35px;
    right: 42%;
    font-size: 45px;
}

.decoration-two {
    bottom: 30px;
    left: 45%;
    font-size: 35px;
}

.decoration-three {
    top: 55px;
    right: 8%;
    font-size: 27px;
}

.welcome-section {
    text-align: center;
    max-width: 690px;
    margin: 85px auto 80px;
}

.welcome-section h2 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(25px, 3vw, 35px);
    line-height: 1.4;
    font-weight: 600;
    margin: 16px 0;
    color: #65525d;
}

.welcome-section p {
    color: #93828b;
    font-size: 13px;
    line-height: 1.9;
}

.topics-section {
    padding-bottom: 70px;
}

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: end;
    margin-bottom: 28px;
}

.section-heading h2 {
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    font-weight: 600;
    color: #66515c;
    margin: 9px 0;
}

.section-heading p {
    color: #98858e;
    font-size: 13px;
}

.topic-count {
    color: #b38a9b;
    border: 1px solid #ead8df;
    border-radius: 30px;
    padding: 9px 15px;
    font-size: 10px;
    letter-spacing: 1px;
    font-weight: 700;
}

.topic-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.topic-card {
    border-radius: 19px;
    padding: 25px;
    min-height: 270px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: .25s;
    border: 1px solid rgba(255,255,255,.8);
}

.topic-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 14px 35px rgba(110, 79, 94, .09);
}

.topic-card.pink {
    background: #f9e8ed;
}

.topic-card.peach {
    background: #fbece5;
}

.topic-card.lavender {
    background: #f0eafa;
}

.topic-card.blue {
    background: #eaf0f6;
}

.topic-card.rose {
    background: #f8e8e7;
}

.topic-card.cream {
    background: #f5eee3;
}

.card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-number {
    font-size: 11px;
    letter-spacing: 1px;
    color: #a58e99;
    font-weight: 700;
}

.card-icon {
    width: 39px;
    height: 39px;
    border-radius: 50%;
    background: rgba(255,255,255,.65);
    display: flex;
    justify-content: center;
    align-items: center;
    color: #bd8299;
    font-size: 21px;
}

.card-category {
    display: block;
    color: #a98796;
    font-size: 9px;
    letter-spacing: 1.3px;
    font-weight: 700;
    margin-bottom: 10px;
}

.card-content h3 {
    font-family: 'Playfair Display', serif;
    font-size: 23px;
    font-weight: 600;
    color: #65525d;
    margin-bottom: 10px;
}

.card-content p {
    color: #8d7c85;
    font-size: 12px;
    line-height: 1.8;
}

.card-button {
    align-self: flex-start;
    border: none;
    background: transparent;
    color: #ae718b;
    font-family: 'DM Sans', sans-serif;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 20px;
    transition: .2s;
}

.card-button:hover {
    color: #815269;
}

.card-button span {
    margin-left: 7px;
    font-size: 15px;
}

.love-quote {
    max-width: 780px;
    margin: 15px auto 90px;
    text-align: center;
    padding: 35px 40px;
    background: #fbf0f2;
    border-radius: 22px;
}

.quote-icon {
    font-family: Georgia, serif;
    font-size: 55px;
    line-height: .8;
    color: #d19caf;
}

.love-quote p {
    font-family: 'Playfair Display', serif;
    font-size: 21px;
    line-height: 1.6;
    color: #745d69;
    margin: 15px 0;
}

.quote-caption {
    color: #b38b9c;
    font-size: 9px;
    letter-spacing: 1.5px;
    font-weight: 700;
}

.love-footer {
    text-align: center;
    padding: 35px 15px;
    border-top: 1px solid #f0e4e8;
}

.footer-logo {
    font-family: 'Playfair Display', serif;
    font-size: 25px;
    color: #755c69;
    font-weight: 700;
}

.footer-logo span {
    color: #c77d99;
    margin-left: 3px;
}

.love-footer p {
    font-size: 12px;
    color: #9a8790;
    margin: 8px 0 17px;
}

.love-footer > span {
    color: #b6a5ad;
    font-size: 10px;
}

/* POP-UP MATERI */

.topic-modal {
    position: fixed;
    inset: 0;
    z-index: 999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    opacity: 0;
    visibility: hidden;
    transition: .25s;
}

.topic-modal.active {
    opacity: 1;
    visibility: visible;
}

.modal-overlay {
    position: absolute;
    inset: 0;
    background: rgba(58, 42, 51, .48);
    backdrop-filter: blur(5px);
}

.modal-content {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 620px;
    max-height: 85vh;
    overflow-y: auto;
    background: #fffaf9;
    border-radius: 22px;
    padding: 38px;
    transform: translateY(20px);
    transition: .25s;
    box-shadow: 0 20px 70px rgba(60, 40, 52, .15);
}

.topic-modal.active .modal-content {
    transform: translateY(0);
}

.modal-close {
    position: absolute;
    top: 15px;
    right: 19px;
    border: none;
    background: #f6e9ed;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    color: #a36f85;
    font-size: 23px;
    cursor: pointer;
}

.modal-category {
    display: block;
    color: #bc8199;
    font-size: 10px;
    letter-spacing: 1.5px;
    font-weight: 700;
    margin-bottom: 10px;
    padding-right: 35px;
}

.modal-content h2 {
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    color: #68535f;
    margin-bottom: 12px;
}

.modal-content h3 {
    color: #a8798e;
    font-size: 15px;
    margin-bottom: 20px;
}

.modal-body p {
    color: #82727b;
    font-size: 13px;
    line-height: 1.9;
    margin-bottom: 13px;
}

.reflection-box {
    background: #f8eaf0;
    border-radius: 14px;
    padding: 18px;
    margin-top: 25px;
}

.reflection-box span {
    color: #b77992;
    font-size: 9px;
    letter-spacing: 1px;
    font-weight: 700;
}

.reflection-box p {
    color: #765d6a;
    font-family: 'Playfair Display', serif;
    font-size: 16px;
    line-height: 1.6;
    margin-top: 9px;
}

.modal-done {
    width: 100%;
    border: none;
    background: #bd8299;
    color: white;
    padding: 13px;
    border-radius: 30px;
    margin-top: 20px;
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.modal-done:hover {
    background: #a96d85;
}

/* TAMPILAN RESPONSIF */

@media (max-width: 900px) {
    .love-page {
        padding: 0 5%;
    }

    .love-hero {
        padding: 50px 6%;
    }

    .hero-illustration {
        transform: scale(.85);
        margin-right: 0;
    }

    .topic-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .love-header {
        min-height: 70px;
    }

    .header-label {
        font-size: 8px;
        letter-spacing: 1px;
    }

    .back-link {
        font-size: 11px;
    }

    .love-hero {
        min-height: auto;
        padding: 43px 25px;
        border-radius: 20px;
    }

    .hero-content h1 {
        font-size: 39px;
    }

    .hero-content p {
        font-size: 12px;
    }

    .hero-illustration {
        display: none;
    }

    .welcome-section {
        margin: 65px auto;
    }

    .welcome-section h2 {
        font-size: 25px;
    }

    .welcome-section p {
        font-size: 12px;
    }

    .section-heading {
        align-items: flex-start;
        gap: 15px;
    }

    .section-heading h2 {
        font-size: 25px;
    }

    .section-heading p {
        font-size: 11px;
    }

    .topic-count {
        font-size: 8px;
        padding: 8px 10px;
        white-space: nowrap;
    }

    .topic-grid {
        grid-template-columns: 1fr;
        gap: 14px;
    }

    .topic-card {
        min-height: 245px;
    }

    .love-quote {
        padding: 28px 22px;
        margin-bottom: 60px;
    }

    .love-quote p {
        font-size: 18px;
    }

    .modal-content {
        padding: 30px 23px;
        max-height: 88vh;
    }

    .modal-content h2 {
        font-size: 27px;
    }
}
```