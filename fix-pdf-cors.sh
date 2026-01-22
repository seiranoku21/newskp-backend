#!/bin/bash

# ============================================================================
# Fix PDF CORS - Clear Cache and Optimize
# ============================================================================
# Script ini membersihkan cache dan optimize Laravel setelah perubahan CORS
# Jalankan setelah pull/update kode terkait PDF preview fix
# ============================================================================

echo "🚀 Starting PDF CORS Fix..."
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Step 1: Clear Config Cache
echo -e "${YELLOW}📦 Step 1/5: Clearing config cache...${NC}"
php artisan config:clear
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Config cache cleared${NC}"
else
    echo -e "${RED}❌ Failed to clear config cache${NC}"
    exit 1
fi
echo ""

# Step 2: Clear Route Cache
echo -e "${YELLOW}🛣️  Step 2/5: Clearing route cache...${NC}"
php artisan route:clear
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Route cache cleared${NC}"
else
    echo -e "${RED}❌ Failed to clear route cache${NC}"
    exit 1
fi
echo ""

# Step 3: Clear Application Cache
echo -e "${YELLOW}🗑️  Step 3/5: Clearing application cache...${NC}"
php artisan cache:clear
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Application cache cleared${NC}"
else
    echo -e "${RED}❌ Failed to clear application cache${NC}"
    exit 1
fi
echo ""

# Step 4: Clear View Cache
echo -e "${YELLOW}👁️  Step 4/5: Clearing view cache...${NC}"
php artisan view:clear
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ View cache cleared${NC}"
else
    echo -e "${RED}❌ Failed to clear view cache${NC}"
    exit 1
fi
echo ""

# Step 5: Optimize (production only)
if [ "$APP_ENV" = "production" ]; then
    echo -e "${YELLOW}⚡ Step 5/5: Optimizing for production...${NC}"
    php artisan optimize
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Optimization complete${NC}"
    else
        echo -e "${RED}❌ Optimization failed${NC}"
        exit 1
    fi
else
    echo -e "${YELLOW}⚡ Step 5/5: Skipping optimization (not production)${NC}"
    echo -e "${GREEN}✅ Development mode - no optimization needed${NC}"
fi
echo ""

# Verify routes are registered
echo -e "${YELLOW}🔍 Verifying routes...${NC}"
ROUTE_COUNT=$(php artisan route:list | grep -c "uploads/files")
if [ "$ROUTE_COUNT" -ge 2 ]; then
    echo -e "${GREEN}✅ File upload routes found: $ROUTE_COUNT routes${NC}"
else
    echo -e "${RED}❌ Warning: File upload routes not found or incomplete${NC}"
    echo -e "${YELLOW}   Expected 2 routes (GET and OPTIONS), found: $ROUTE_COUNT${NC}"
fi
echo ""

# Success message
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}✨ PDF CORS Fix Applied Successfully! ✨${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Test PDF preview di browser"
echo "2. Check browser console untuk errors"
echo "3. Verify CORS headers dengan: curl -I -H 'Origin: http://localhost:3000' [PDF_URL]"
echo ""
echo -e "${GREEN}Happy coding! 🚀${NC}"
