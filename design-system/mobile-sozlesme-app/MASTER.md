
# Design System Master File

> **LOGIC:** When building a specific mobile page, first check:
> `design-system/mobile-pages/[page-name].md`
>
> If that file exists, its rules override this Master file.
> Otherwise strictly follow the rules below.

---

# Project

**Project:** Mobile Sozlesme App
**Platform:** Mobile First
**Style:** shadcn/ui Inspired Mobile SaaS
**Theme:** Premium Dark Mobile Dashboard
**Design Language:** Clean, Layered, Minimal, Native-App Feel

---

# Core Mobile Philosophy

The app should feel like:

* Native iOS/Android app
* Modern fintech app
* Enterprise mobile SaaS
* Smooth and lightweight
* Gesture-friendly
* Thumb-accessible
* Minimal but premium

Inspired by:

* Linear Mobile
* Stripe Mobile Dashboard
* Notion Mobile
* Revolut
* Raycast

---

# Mobile Layout Rules

## Mobile First

Always design for:

* 375px width first

Then scale upward.

---

## Safe Area Support

All screens MUST support:

```css id="ygfj4l"
padding-top: env(safe-area-inset-top);
padding-bottom: env(safe-area-inset-bottom);
```

---

## App Shell

```css id="sbh3b9"
.mobile-shell {
  min-height: 100vh;

  background: hsl(var(--background));

  color: hsl(var(--foreground));

  overflow-x: hidden;
}
```

---

# Typography

## Font System

| Usage         | Font           |
| ------------- | -------------- |
| UI / Body     | Inter          |
| Numbers / KPI | JetBrains Mono |

---

## Font Import

```css id="1w1k8y"
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap');
```

---

# Color Tokens

```css id="s3p1sj"
:root {
  --background: 222 47% 4%;
  --foreground: 210 40% 98%;

  --card: 222 47% 6%;
  --card-foreground: 210 40% 98%;

  /* Brand Primary #171717 */
  --primary: 0 0% 9%;
  --primary-foreground: 0 0% 100%;

  --secondary: 217 33% 17%;
  --secondary-foreground: 210 40% 98%;

  --muted: 217 33% 17%;
  --muted-foreground: 215 20% 65%;

  --accent: 217 33% 20%;
  --accent-foreground: 210 40% 98%;

  --border: 217 33% 22%;
  --ring: 0 0% 9%;

  --radius: 0.75rem; /* Md: 12px */
}
```

---

# Mobile Spacing System

| Token   | Value |
| ------- | ----- |
| space-1 | 4px   |
| space-2 | 8px   |
| space-3 | 12px  |
| space-4 | 16px  |
| space-5 | 20px  |
| space-6 | 24px  |
| space-8 | 32px  |

---

# Radius Rules

| Component         | Radius |
| ----------------- | ------ |
| Buttons           | 14px   |
| Inputs            | 14px   |
| Cards             | 24px   |
| Sheets            | 28px   |
| Bottom Navigation | 24px   |

Large radius preferred for premium mobile feel.

---

# Motion System

```css id="qq6grs"
:root {
  --transition-fast:
    150ms cubic-bezier(.2,.8,.2,1);

  --transition-base:
    250ms cubic-bezier(.2,.8,.2,1);

  --transition-slow:
    400ms cubic-bezier(.2,.8,.2,1);
}
```

---

# Surface Hierarchy

| Layer      | Usage                   |
| ---------- | ----------------------- |
| Background | App background          |
| Card       | Primary surface         |
| Secondary  | Inputs / filters        |
| Accent     | Hover / active          |
| Overlay    | Bottom sheets / dialogs |

---

# Buttons

## Primary Button

```css id="e9d7gf"
.btn-primary {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 100%;
  height: 52px;

  border-radius: 14px;

  background: hsl(var(--primary));
  color: hsl(var(--primary-foreground));

  font-weight: 600;
  font-size: 15px;

  border: none;

  transition:
    opacity var(--transition-base),
    transform var(--transition-base);

  cursor: pointer;
}

.btn-primary:active {
  transform: scale(0.98);
}
```

---

## Secondary Button

```css id="u7j52g"
.btn-secondary {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 100%;
  height: 52px;

  border-radius: 14px;

  background: hsl(var(--secondary));

  border: 1px solid hsl(var(--border));

  color: hsl(var(--foreground));

  transition: background-color var(--transition-base);

  cursor: pointer;
}
```

---

# Cards

```css id="e0m4mv"
.card {
  border-radius: 24px;

  border: 1px solid hsl(var(--border));

  background: hsl(var(--card));

  padding: 20px;

  transition:
    border-color var(--transition-base),
    background-color var(--transition-base);
}
```

---

# KPI Cards

```css id="r7ej6q"
.kpi-card {
  position: relative;

  overflow: hidden;
}

.kpi-card::after {
  content: "";

  position: absolute;
  inset: 0;

  background:
    radial-gradient(
      circle at top right,
      rgba(34,197,94,0.10),
      transparent 45%
    );

  pointer-events: none;
}
```

---

# Inputs

```css id="qagqcv"
.input {
  width: 100%;

  height: 52px;

  border-radius: 14px;

  border: 1px solid hsl(var(--border));

  background: hsl(var(--secondary));

  padding: 0 16px;

  color: hsl(var(--foreground));

  font-size: 16px;

  transition:
    border-color var(--transition-base),
    box-shadow var(--transition-base);
}

.input:focus {
  outline: none;

  border-color: hsl(var(--ring));

  box-shadow:
    0 0 0 3px rgba(34,197,94,0.14);
}
```

---

# Mobile Navigation

## Bottom Navigation

```css id="o5x6g1"
.bottom-nav {
  position: fixed;

  bottom: 16px;
  left: 16px;
  right: 16px;

  height: 68px;

  border-radius: 24px;

  border: 1px solid hsl(var(--border));

  background:
    rgba(15,23,42,0.88);

  backdrop-filter: blur(18px);

  display: flex;
  align-items: center;
  justify-content: space-around;

  z-index: 50;
}
```

---

# Top Header

```css id="y3jsj5"
.mobile-header {
  position: sticky;

  top: 0;

  z-index: 40;

  backdrop-filter: blur(14px);

  background:
    rgba(9,9,11,0.72);

  border-bottom:
    1px solid hsl(var(--border));
}
```

---

# Bottom Sheets

Use bottom sheets instead of desktop modals.

```css id="y1a6z2"
.bottom-sheet {
  position: fixed;

  bottom: 0;
  left: 0;
  right: 0;

  border-top-left-radius: 28px;
  border-top-right-radius: 28px;

  background: hsl(var(--card));

  border-top: 1px solid hsl(var(--border));

  padding: 24px;
}
```

---

# Lists

## Mobile List Item

```css id="0e61ih"
.list-item {
  display: flex;
  align-items: center;
  justify-content: space-between;

  min-height: 64px;

  padding: 16px;

  border-radius: 18px;

  transition: background-color var(--transition-fast);

  cursor: pointer;
}

.list-item:active {
  background: rgba(255,255,255,0.04);
}
```

---

# Charts

## Mobile Chart Rules

* Horizontal scroll allowed
* Compact legends
* Large touch targets
* Minimal labels
* Smooth curves only
* No overloaded data

---

# Gestures

Support:

* Swipe
* Pull to refresh
* Drag sheets
* Touch feedback
* Momentum scrolling

---

# Accessibility

## Required

* Minimum touch target: 44px
* Visible focus states
* High contrast text
* Reduced motion support
* No tiny text
* No horizontal overflow

---

# Responsive Rules

## Mobile Widths

| Device | Width |
| ------ | ----- |
| Small  | 375px |
| Medium | 390px |
| Large  | 428px |
| Tablet | 768px |

---

# Icon Rules

Use ONLY:

* Lucide Icons

Rules:

* Stroke width: 1.75
* No emoji icons
* Consistent sizing
* Use 20px or 24px icons

---

# Anti-Patterns

## Forbidden

* Desktop-style sidebars
* Tiny buttons
* Heavy shadows
* Neon effects
* Overcrowded tables
* Multiple accent colors
* Tiny chart labels
* Sharp corners
* Full-screen blocking modals
* Hover-only interactions

---

# Preferred Stack

| Layer      | Technology       |
| ---------- | ---------------- |
| UI         | shadcn/ui        |
| Styling    | Tailwind CSS     |
| Components | Radix UI         |
| Icons      | Lucide           |
| Charts     | Recharts         |
| Lists      | TanStack Virtual |
| Theme      | next-themes      |
| Animation  | Framer Motion    |

---

# Final Design Direction

The app should feel like:

* Premium native mobile app
* Modern fintech dashboard
* Minimal enterprise SaaS
* Fast and lightweight
* Gesture-first
* Touch-friendly
* Layered and polished

NOT like:

* Bootstrap mobile sites
* Old Android admin panels
* Desktop dashboard shrunk to mobile
* Neon cyberpunk UI
* Heavy glassmorphism templates






























<!-- # Design System Master File

> **LOGIC:** When building a specific page, first check `design-system/pages/[page-name].md`.
> If that file exists, its rules **override** this Master file.
> If not, strictly follow the rules below.

---

**Project:** Mobile Sozlesme App
**Generated:** 2026-05-17 18:46:14
**Category:** Analytics Dashboard

---

## Global Rules

### Color Palette

| Role | Hex | CSS Variable |
|------|-----|--------------|
| Primary | `#0F172A` | `--color-primary` |
| Secondary | `#1E293B` | `--color-secondary` |
| CTA/Accent | `#22C55E` | `--color-cta` |
| Background | `#020617` | `--color-background` |
| Text | `#F8FAFC` | `--color-text` |

**Color Notes:** Dark bg + green positive indicators

### Typography

- **Heading Font:** Fira Code
- **Body Font:** Fira Sans
- **Mood:** dashboard, data, analytics, code, technical, precise
- **Google Fonts:** [Fira Code + Fira Sans](https://fonts.google.com/share?selection.family=Fira+Code:wght@400;500;600;700|Fira+Sans:wght@300;400;500;600;700)

**CSS Import:**
```css
@import url('https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600;700&family=Fira+Sans:wght@300;400;500;600;700&display=swap');
```

### Spacing Variables

| Token | Value | Usage |
|-------|-------|-------|
| `--space-xs` | `4px` / `0.25rem` | Tight gaps |
| `--space-sm` | `8px` / `0.5rem` | Icon gaps, inline spacing |
| `--space-md` | `16px` / `1rem` | Standard padding |
| `--space-lg` | `24px` / `1.5rem` | Section padding |
| `--space-xl` | `32px` / `2rem` | Large gaps |
| `--space-2xl` | `48px` / `3rem` | Section margins |
| `--space-3xl` | `64px` / `4rem` | Hero padding |

### Shadow Depths

| Level | Value | Usage |
|-------|-------|-------|
| `--shadow-sm` | `0 1px 2px rgba(0,0,0,0.05)` | Subtle lift |
| `--shadow-md` | `0 4px 6px rgba(0,0,0,0.1)` | Cards, buttons |
| `--shadow-lg` | `0 10px 15px rgba(0,0,0,0.1)` | Modals, dropdowns |
| `--shadow-xl` | `0 20px 25px rgba(0,0,0,0.15)` | Hero images, featured cards |

---

## Component Specs

### Buttons

```css
/* Primary Button */
.btn-primary {
  background: #22C55E;
  color: white;
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 600;
  transition: all 200ms ease;
  cursor: pointer;
}

.btn-primary:hover {
  opacity: 0.9;
  transform: translateY(-1px);
}

/* Secondary Button */
.btn-secondary {
  background: transparent;
  color: #0F172A;
  border: 2px solid #0F172A;
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 600;
  transition: all 200ms ease;
  cursor: pointer;
}
```

### Cards

```css
.card {
  background: #020617;
  border-radius: 12px;
  padding: 24px;
  box-shadow: var(--shadow-md);
  transition: all 200ms ease;
  cursor: pointer;
}

.card:hover {
  box-shadow: var(--shadow-lg);
  transform: translateY(-2px);
}
```

### Inputs

```css
.input {
  padding: 12px 16px;
  border: 1px solid #E2E8F0;
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 200ms ease;
}

.input:focus {
  border-color: #0F172A;
  outline: none;
  box-shadow: 0 0 0 3px #0F172A20;
}
```

### Modals

```css
.modal-overlay {
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
}

.modal {
  background: white;
  border-radius: 16px;
  padding: 32px;
  box-shadow: var(--shadow-xl);
  max-width: 500px;
  width: 90%;
}
```

---

## Style Guidelines

**Style:** Data-Dense Dashboard

**Keywords:** Multiple charts/widgets, data tables, KPI cards, minimal padding, grid layout, space-efficient, maximum data visibility

**Best For:** Business intelligence dashboards, financial analytics, enterprise reporting, operational dashboards, data warehousing

**Key Effects:** Hover tooltips, chart zoom on click, row highlighting on hover, smooth filter animations, data loading spinners

### Page Pattern

**Pattern Name:** App Store Style Landing

- **Conversion Strategy:** Show real screenshots. Include ratings (4.5+ stars). QR code for mobile. Platform-specific CTAs.
- **CTA Placement:** Download buttons prominent (App Store + Play Store) throughout
- **Section Order:** 1. Hero with device mockup, 2. Screenshots carousel, 3. Features with icons, 4. Reviews/ratings, 5. Download CTAs

---

## Anti-Patterns (Do NOT Use)

- ❌ Ornate design
- ❌ No filtering

### Additional Forbidden Patterns

- ❌ **Emojis as icons** — Use SVG icons (Heroicons, Lucide, Simple Icons)
- ❌ **Missing cursor:pointer** — All clickable elements must have cursor:pointer
- ❌ **Layout-shifting hovers** — Avoid scale transforms that shift layout
- ❌ **Low contrast text** — Maintain 4.5:1 minimum contrast ratio
- ❌ **Instant state changes** — Always use transitions (150-300ms)
- ❌ **Invisible focus states** — Focus states must be visible for a11y

---

## Pre-Delivery Checklist

Before delivering any UI code, verify:

- [ ] No emojis used as icons (use SVG instead)
- [ ] All icons from consistent icon set (Heroicons/Lucide)
- [ ] `cursor-pointer` on all clickable elements
- [ ] Hover states with smooth transitions (150-300ms)
- [ ] Light mode: text contrast 4.5:1 minimum
- [ ] Focus states visible for keyboard navigation
- [ ] `prefers-reduced-motion` respected
- [ ] Responsive: 375px, 768px, 1024px, 1440px
- [ ] No content hidden behind fixed navbars
- [ ] No horizontal scroll on mobile -->
