# Tailwind CSS Offline Setup Guide

## Changes Made

1. **Created `tailwind.config.js`** - Configuration file for Tailwind CSS v4 with:
   - Custom breakpoints matching your theme
   - Outfit font family configuration
   - Dark mode support via CSS class

2. **Updated `src/css/style.css`** - Removed Google Fonts CDN import and added:
   - Local @font-face declaration for Outfit font
   - Reference to local font file at `src/fonts/outfit-variable.ttf`

3. **Created `src/fonts/` directory** - Ready for local font files

## Next Steps to Complete Offline Setup

### Download Outfit Font

You need to download the Outfit font variable file and place it in `src/fonts/`:

1. Go to: https://fonts.google.com/download?family=Outfit
2. Download the Outfit font family (variable font recommended)
3. Extract and copy `Outfit-VariableFont_wght.ttf` to `src/fonts/`
4. Rename it to `outfit-variable.ttf` (or update the @font-face path in style.css accordingly)

### Alternative: Use System Installation
If you want to use your system's Outfit font instead:
- Update `src/css/style.css` @font-face to point to system fonts (if available)
- Or remove the @font-face declaration if Outfit is system-installed

### Verify Installation

1. Install dependencies if not done:
   ```bash
   npm install
   ```

2. Start development server:
   ```bash
   npm start
   ```

3. Build for production (offline):
   ```bash
   npm run build
   ```

## Benefits of This Setup

✅ Tailwind CSS fully processed locally during build
✅ No external CDN dependencies required
✅ Fonts loaded locally
✅ Entire site can work offline once built
✅ Faster page loads (no external requests)
✅ Better privacy (no external tracking)

## Additional Offline Resources

If your site uses other external libraries, check:
- `vendor/` directory - PHP dependencies (already local)
- `node_modules/` - JavaScript dependencies (already local)
- Check HTML files for external CDN links (e.g., ApexCharts, FullCalendar CDN)

You may need to configure these to use local versions or offline alternatives.
