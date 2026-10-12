---
name: OmniDesk
description: Neo-brutalist communication workspace with sci-fi HUD instrumentation.
colors:
  light-page: "#e8e9df"
  light-surface: "#fbfcf5"
  light-muted-surface: "#edf0e6"
  light-raised-surface: "#ffffff"
  light-border: "#65736e"
  light-edge: "#172f33"
  light-text: "#102d32"
  light-muted-text: "#496064"
  light-accent: "#00636e"
  light-accent-hover: "#004650"
  light-accent-soft: "#d3eeeb"
  action: "#cafd47"
  action-ink: "#182900"
  light-amber: "#805000"
  light-amber-soft: "#fff0bf"
  light-success: "#176348"
  light-success-soft: "#d6eee2"
  light-error: "#9c252d"
  light-error-soft: "#ffe3e3"
  light-glow: "#007d8f36"
  light-shadow: "#172f33"
  light-glass: "#fbfcf5e8"
  dark-page: "#080f15"
  dark-surface: "#101e27"
  dark-muted-surface: "#0b161e"
  dark-raised-surface: "#172832"
  dark-border: "#4e737e"
  dark-edge: "#8bafb5"
  dark-text: "#e8f6f1"
  dark-muted-text: "#a6bfc6"
  dark-accent: "#64e6e6"
  dark-accent-hover: "#a0ffff"
  dark-accent-soft: "#193a42"
  dark-amber: "#ffcc73"
  dark-amber-soft: "#3d301e"
  dark-success: "#99eec4"
  dark-success-soft: "#18392e"
  dark-error: "#ffb5b8"
  dark-error-soft: "#40212c"
  dark-glow: "#50ffff36"
  dark-shadow: "#02070a"
  dark-glass: "#101e27e8"
typography:
  display:
    fontFamily: "'Space Grotesk', 'Segoe UI', sans-serif"
    fontSize: "clamp(38px, 4.6vw, 68px)"
    fontWeight: 700
    lineHeight: 1.02
    letterSpacing: "-.035em"
  headline:
    fontFamily: "'Space Grotesk', 'Segoe UI', sans-serif"
    fontSize: "clamp(24px, 2.6vw, 36px)"
    lineHeight: 1.15
    letterSpacing: "-.035em"
  brand:
    fontFamily: "'Space Grotesk', 'Segoe UI', sans-serif"
    fontSize: "25px"
    fontWeight: 700
    letterSpacing: "-.04em"
  message:
    fontFamily: "'Segoe UI', sans-serif"
    fontSize: "15px"
    lineHeight: 1.8
  telegram-message:
    fontFamily: "'Segoe UI', sans-serif"
    fontSize: "14px"
    lineHeight: 1.8
  label:
    fontFamily: "'Space Grotesk', 'Segoe UI', sans-serif"
    fontSize: "12px"
    fontWeight: 700
  button:
    fontFamily: "'Space Grotesk', 'Segoe UI', sans-serif"
    fontSize: "13px"
    fontWeight: 700
    lineHeight: 1.35
rounded:
  control: "2px"
  avatar: "3px"
  telegram-bubble: "2px 10px 10px 10px"
spacing:
  gap-8: "8px"
  gap-12: "12px"
  gap-20: "20px"
  workspace-padding: "24px"
  auth-padding: "30px"
components:
  button-primary:
    backgroundColor: "{colors.action}"
    textColor: "{colors.action-ink}"
    typography: "{typography.button}"
    rounded: "{rounded.control}"
    padding: "9px 15px"
  input-light:
    backgroundColor: "{colors.light-raised-surface}"
    textColor: "{colors.light-text}"
    rounded: "{rounded.control}"
    padding: "10px 12px"
  input-dark:
    backgroundColor: "{colors.dark-raised-surface}"
    textColor: "{colors.dark-text}"
    rounded: "{rounded.control}"
    padding: "10px 12px"
  read-badge-light:
    backgroundColor: "{colors.light-success-soft}"
    textColor: "{colors.light-success}"
    padding: "5px 8px"
  read-badge-dark:
    backgroundColor: "{colors.dark-success-soft}"
    textColor: "{colors.dark-success}"
    padding: "5px 8px"
---

# Design System: OmniDesk

## Overview

**Creative North Star: "Dense sci-fi control room"**

Neo-brutalist frames, hard offset shadows, lime actions, and cyan instrumentation establish a tactile communication workspace. Stronger glow and animated provider routes express the user's chosen HUD direction; readable communication stays central in both light and dark themes. The implementation is code-first React/CSS/SVG.

**Key Characteristics:** dense operational controls, angular structure, transparent header/status surfaces, opaque reading surfaces, explicit state labels, optional motion.

## Colors

Light mode pairs warm pale surfaces with deep teal ink; dark mode pairs blue-black surfaces with pale cyan accents. Lime primary actions retain the same dark ink in both themes. The frontmatter records exact theme values from `frontend/src/index.css`; runtime components use semantic CSS custom properties.

Success, unread/waiting, and error states use their theme-specific green, amber, and red text with matching opaque soft surfaces. Pair color with text. Translucent `--glass` is reserved for the existing header and authentication status treatment; message surfaces remain opaque.

## Typography

Space Grotesk is self-hosted at `/fonts/SpaceGrotesk.ttf`, weights 300–700, with `font-display: swap` and Segoe UI/sans-serif fallback. Its OFL license ships at `frontend/public/fonts/OFL.txt`; preserve that license alongside the font. Message prose uses Segoe UI/sans-serif rather than display typography.

The display role belongs to authentication copy; the responsive headline role belongs to workspace headings. Labels and compact metadata maintain the dense control-room hierarchy. Email prose has a 72ch maximum and 1.8 line height; Telegram prose also uses 1.8. Counts and timestamps use tabular numerals.

## Layout

The app caps at 1800px with 20px outer padding; authentication caps at 1260px with two columns. Gmail uses a 260–340px list plus flexible detail pane. Telegram uses list, conversation, and information columns; its information pane disappears at 1200px.

At 900px authentication stacks and workspace controls wrap. At 720px message panes stack, the provider overview becomes a Show chart/Hide chart disclosure, and filters become an expandable labeled disclosure. Search remains available. At 480px filter fields and narrow action groups stack. Preserve the 320px minimum document width, wrapping addresses, stable scroll gutters, and independent message scrolling.

## Elevation & Depth

Hard offset shadows provide structure; soft theme-colored glow adds HUD depth. Workspace shadow is `6px 6px 0 var(--shadow), 0 10px 40px var(--glow)`; authentication uses an 8px hard offset with the same ambient glow. Primary buttons lift from a 3px offset to 5px on hover and press down on activation. Blur belongs to translucent chrome, not message prose.

## Shapes

Frames use 1px divider borders or 2px structural borders, with mostly square panels and 2px control corners. Avatars use 3px corners; Telegram bubbles use `2px 10px 10px 10px`. The clipped SVG brand mark and provider hub supply the signature angular silhouette.

## Components

Primary buttons use lime, dark ink, 2px edge borders, 42px minimum height, and `9px 15px` padding. Secondary and theme/motion controls use opaque surfaces. Preserve disabled styles and the 3px visible focus outline; fields use a 44px minimum height and accent focus treatment.

Provider tabs use explicit selected border, surface, and text changes. Conversation selection combines accent-soft fill and an inset outline. Read/unread badges retain text labels. Gmail HTML remains in its existing sandboxed white reading frame; plaintext and Telegram messages use opaque theme surfaces.

The overview chart describes the loaded page or loaded chats, not whole-account activity. Its unread segment animates using `scaleX` with a 400ms transform transition. The provider diagram illustrates supported providers and does not assert connection status. Loading/error states do not display invented counts.

Motion preference persists under `omni_desk_motion`; the document motion attribute suppresses animations and transitions. The OS reduced-motion media query always suppresses motion, including when a stored preference requests full motion. Keep the control label aligned with effective motion when OS settings override it. Theme selection persists under `omni_desk_theme`.

## Do's and Don'ts

- **Do** preserve authentication, verification, CAPTCHA, authorization, synchronization, search, filters, ownership boundaries, and read-only Telegram behavior.
- **Do** keep state text readable in both themes and include labels alongside semantic colors.
- **Do** preserve mobile overview/filter disclosures, self-hosted font licensing, keyboard focus, and motion reduction.
- **Don't** present decorative diagrams or loaded-view counts as real-time provider health or invented operational measurements.
- **Don't** extend glass transparency into message reading surfaces or imply sending/AI generation beyond the implemented MVP.
