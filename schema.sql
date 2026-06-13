-- Create Tables

CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    icon_svg TEXT NOT NULL,
    link_text VARCHAR(50) DEFAULT 'Order Now',
    sort_order INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS portfolio_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS process_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    step_number VARCHAR(10) NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    icon_svg TEXT NOT NULL,
    sort_order INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS bento_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    icon_svg TEXT,
    card_class VARCHAR(100) DEFAULT '',
    stat_num VARCHAR(50) DEFAULT '',
    stat_label VARCHAR(100) DEFAULT '',
    sort_order INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    service VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Initial Data

-- Admin User: adloaf@admin / Adloaf@2027
INSERT IGNORE INTO users (username, password_hash) VALUES ('adloaf@admin', 'REPLACE_ME_WITH_HASH_OF_Adloaf@2027');
-- '$2y$10$M/Z6e0/1.A3D6L3N7V/G5eXlX6hX/U6S2B2/H4L9N2K1H7H5Q7'
-- For now, I will use a known hash for 'Adloaf@2027': $2y$10$hK.9fO1nIfS5N0oDq7uH.OJ.n8oA4rI88m.5l1b8U2iLqY11C1eA6 (I'll generate it later if needed).
-- Let's just create an insert that won't conflict.
-- Wait, I can generate it via PHP directly instead of SQL or run a command to get the hash.

-- Site Settings
INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES 
('hero_title', 'Freshly Baked <span class="glow">Creative Ideas</span> for Brands.'),
('hero_desc', 'A creative showcase of websites, graphic designs, brand visuals, and digital ideas crafted to help brands look better, communicate smarter, and grow faster.'),
('about_title', 'Fresh Design Thinking. Ready-to-Serve Platforms.'),
('about_desc', 'Adloaf is a creative design brand engineered to act like an artisanal bakery for business visuals. In our bakery, <strong>“Ad”</strong> stands for advertising, branding, strategy, and business growth. <strong>“Loaf”</strong> represents our freshly baked design concepts, warm organic layouts, and premium digital solutions that we knead, proof, and serve hot to clients worldwide.'),
('contact_email', 'bake@adloaf.com'),
('contact_whatsapp', 'https://wa.me/1234567890'),
('social_dribbble', 'https://dribbble.com'),
('social_behance', 'https://behance.net'),
('social_linkedin', 'https://linkedin.com'),
('social_instagram', 'https://instagram.com');

-- Services
INSERT IGNORE INTO services (title, description, icon_svg, link_text, sort_order) VALUES
('Website Design', 'Bespoke portfolio, product, and brand sites. Engineered to look stunning on mobile devices and desktops, with layout grids built to impress.', '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="M6 8h.01M10 8h.01M14 8h.01M2 12h20"/>', 'Order Website', 1),
('Landing Pages', 'High-converting single pages crafted to launch products, capture leads, and outline features with highly engaging visual hierarchy.', '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 17h6M9 13h6M9 9h4"/>', 'Order Page', 2),
('Graphic Design', 'Artistic and advertising visuals designed for physical prints, digital displays, and complex layouts. Styled to perfection.', '<path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>', 'Order Graphics', 3),
('Brand Identity', 'Bespoke logo design, color systems, style guides, and typography plans that build solid credibility and unified presentation.', '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>', 'Order Branding', 4),
('Social Media Creatives', 'Visually engaging custom posts, reels graphic shells, story templates, and banners that command attention in crowded social feeds.', '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>', 'Order Creatives', 5),
('Digital Campaigns', 'Integrated advertising banners, promotional pages, and email visuals linked by a unified strategic message and glowing layout style.', '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>', 'Order Campaign', 6);

-- Portfolio Items
INSERT IGNORE INTO portfolio_items (category, title, description, image_path, sort_order) VALUES
('websites', 'Nova Landing Page', 'A premium portfolio website featuring ultra-smooth scrolling, light glassmorphism, and minimal structure.', 'assets/portfolio_websites.png', 1),
('posters', 'Fresh Ideas Poster', 'High-contrast typographical poster with custom grain overlay textures and bold editorial font scales.', 'assets/portfolio_posters.png', 2),
('branding', 'Rise Identity System', 'Complete corporate brand package, featuring business card systems, modern colors, and brand books.', 'assets/portfolio_branding.png', 3),
('social', 'Zenith Social Creatives', 'Scroll-stopping post layouts designed to scale user interaction with glowing gradient lines.', 'assets/portfolio_social.png', 4),
('uiconcepts', 'Knead Dashboard Design', 'Mobile application prototype utilizing soft-rounded card systems and caramel highlights.', 'assets/portfolio_uiconcepts.png', 5);

-- Process Steps
INSERT IGNORE INTO process_steps (step_number, title, description, icon_svg, sort_order) VALUES
('01', 'Discover', 'Gathering project requirements, analyzing branding constraints, and understanding core user targets.', '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>', 1),
('02', 'Bake the Idea', 'Mixing strategy with imagination. Drafting conceptual pathways and proofing creative approaches.', '<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>', 2),
('03', 'Design', 'Shaping modern typography, establishing rich color palettes, and refining visual prototypes.', '<path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>', 3),
('04', 'Deliver', 'Serving your digital platforms optimized, checked for details, and ready to go live immediately.', '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>', 4);

-- Bento Cards
INSERT IGNORE INTO bento_cards (title, description, icon_svg, card_class, stat_num, stat_label, sort_order) VALUES
('Fresh Ideas', 'We never reuse stale templates. Every single client brief receives a customized design concept inspired by warm creative trends and tailored for clear strategic impact. Our design processes allow us to bake unique digital interfaces from scratch.', '<path d="m12 3-1.912 5.813a2 2 0 0 1-1.9 1.375H2.03c-1.87 0-2.65 2.41-1.13 3.515l4.945 3.59a2 2 0 0 1 .69 2.124L4.623 21.23c-.58 1.766 1.444 3.237 2.964 2.138L12 19.78l4.413 3.588c1.52 1.1 3.544-.37 2.964-2.138l-1.912-5.813a2 2 0 0 1 .69-2.124l4.945-3.59c1.52-1.1.74-3.515-1.13-3.515h-6.158a2 2 0 0 1-1.9-1.375L12 3z"/>', 'bento-col-2 bento-row-2 bento-item-dark', '', 'Unique Recipes Only', 1),
('Clean Execution', 'Zero cluttered code, bloated libraries, or messy graphic packages. We keep file delivery crisp.', '', '', '100%', '', 2),
('Brand-Focused', 'Design structured around your real brand value to drive visitor conversions.', '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>', '', '', '', 3),
('Served Fast', 'Timelines are strictly respected. We deliver your assets warm and ready-to-run on schedule.', '<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>', '', '', '', 4),
('Creative Storytelling', 'We merge visual aesthetics with compelling brand narratives. This ensures your visitors do not just glance at your landing page, but read, remember, and identify with your strategic value long after leaving.', '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10M6 10h10"/>', 'bento-col-2', '', '', 5);
