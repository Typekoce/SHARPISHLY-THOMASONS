#!/bin/bash

# 1. Create the Hierarchy
echo "📁 Creating Documentation Tiers..."
mkdir -p notes/archive
mkdir -p notes/daily

# 2. Layer 1: The Manifesto (Permanent Law)
if [ ! -f CONTEXT.md ]; then
    echo "📜 Creating CONTEXT.md (The Law)..."
    cat <<EOF > CONTEXT.md
# 🏛️ SHARPISHLY-THOMASONS V3: MANIFESTO
> Permanent Architectural Laws. Change only when the system pivots.

## ⚖️ THE CORE LAWS
- **Registry:** Strictly \`App\Core\Registry\`.
- **Logic:** Decoupled PHP (Brain) and Python (Cognition).
- **Storage:** Rooted at \`/var/www/html/storage/\`.
- **Database:** Raw SQL is Forbidden. Use PDO Prepared Statements.
- **Privacy:** No external API calls. Local Inference Only.
EOF
fi

# 3. Layer 2: The Ledger (Actionable Tasks)
if [ ! -f TODO.md ]; then
    echo "📋 Creating TODO.md (The Ledger)..."
    cat <<EOF > TODO.md
# 📅 THE LEDGER
> Verified, actionable tasks only. No thoughts, no rants.

## 🟥 PHASE 1: SYSTEM STABILITY
- [ ] Align Namespaces to \`App\Core\Registry\`
- [ ] Remove \`DbJson\` fallback in \`bootstrap.php\`
- [ ] Confirm MySQL Host Resolution in Docker Bridge

## 🟨 PHASE 2: NEURAL HANDSHAKE
- [ ] Define FastAPI JSON Contract for Embedding
- [ ] Implement Heartbeat Check in Python Container
EOF
fi

# 4. Layer 3: The Daily Dev Log (The Friction)
LOG_FILE="notes/daily/$(date +%Y-%m-%d)-friction.md"
if [ ! -f "$LOG_FILE" ]; then
    echo "📔 Initializing Daily Dev Log for $(date +%F)..."
    cat <<EOF > "$LOG_FILE"
# 🛠️ DEV LOG: $(date +%F)
> Scratchpad for errors, terminal outputs, and research.

## 🔍 CURRENT FRICTION
- [Initial thoughts on today's goals...]

## 📝 SCRATCHPAD
- (Paste error logs here)
- (Notes on Docker network behavior)

## ✅ LESSONS LEARNED
- (Summary to move to CONTEXT.md or TODO.md later)
EOF
fi

echo "✅ Documentation Strategy Initialized."
echo "   - CONTEXT.md: Use for ARCHITECTURE."
echo "   - TODO.md: Use for TASKS."
echo "   - $LOG_FILE: Use for EVERYTHING ELSE."
