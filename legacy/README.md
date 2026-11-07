# Legacy CSS Files Archive

This folder contains the original CSS files that have been refactored into a new unified design system.

## Reason for Refactoring

The original CSS files had multiple design inconsistencies:
- 9 different color palettes used across files
- Inconsistent typography scales
- No unified spacing system
- Button styles varied significantly
- Missing responsive design

## Files in This Archive

1. **premium-home.css** - Hero section styling (DEPRECATED)
   - Replaced by: sections-hero.css
   - Used dark gradient background with glass-morphism effects

2. **sections-premium.css** - Promotional, reviews, and newsletter sections (DEPRECATED)
   - Replaced by: sections-promo.css  
   - Contained mixed color schemes and inconsistent components

3. **storefront.css** - Product catalog and category styling (DEPRECATED)
   - Replaced by: sections-products.css
   - Used different color palette than other sections

## New Design System Structure

The homepage now uses a modular, unified design system:

```
design-system.css        ← Foundation (colors, typography, spacing, shadows)
├── components.css       ← Buttons, cards, badges, forms
├── sections-hero.css    ← Hero section
├── sections-products.css ← Product grids
├── sections-promo.css   ← Promo, reviews, newsletter
└── responsive.css       ← Mobile/tablet/accessibility
```

Plus existing navbar:
- styles.css             ← Navigation/header (kept as-is, already excellent)

## Migration Impact

- **Visual**: Changed from dark hero to light aesthetic throughout
- **File Size**: CSS now modular and more manageable (~3KB per section vs monolithic files)
- **Maintenance**: Single source of truth for design tokens (design-system.css)
- **Responsive**: All sections now have consistent mobile/tablet breakpoints
- **Compatibility**: All old page logic and PHP remains unchanged

## If You Need to Revert

To restore the old design:
1. Keep a git backup of this version
2. Restore files from this legacy folder
3. Update index.php CSS links back to the original files

---
**Archived**: March 23, 2026
**Reason**: Complete homepage redesign with light aesthetic and unified design system
