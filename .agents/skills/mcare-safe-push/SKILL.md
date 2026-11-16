---
name: mcare-safe-push
description: >-
  Standard operating procedure and automated runbook for safely testing,
  auditing, committing, and pushing code changes in the MCARE repository.
  Use this skill whenever the user asks to push changes to GitHub, prepare a
  commit, verify deployability, or ensure the codebase is clean and secure before pushing.
---

# MCARE Safe Git Push & Pre-Deployment Runbook

This skill provides a standardized, battle-tested procedure to ensure that no broken code, failing tests, untracked cache files, or exposed credentials (`.env`) ever get pushed to GitHub.

---

## 🛠️ Automated Pre-Push Checklist

Before staging or committing any code, execute the pre-push check script:

```powershell
powershell -ExecutionPolicy Bypass -File .agents/skills/mcare-safe-push/scripts/pre-push-check.ps1
```

This automated script performs:
1. **Cache sanitization**: Clears compiled Blade views (`view:clear`) and config cache (`config:clear`).
2. **View churn cleanup**: Restores git-tracked `storage/framework/views` so temporary compiled views are not accidentally modified or deleted in git.
3. **Secret audit**: Scans the git diff for leaked API keys, tokens, or plaintext credentials (`sk_live`, `sk_test`, `password =`, etc.).
4. **Syntax check (`php -l`)**: Verifies every modified PHP file for zero syntax errors.
5. **Full test suite execution (`php artisan test`)**: Runs all 483+ feature and unit tests using the isolated SQLite testing environment.

---

## 📋 Safe Staging and Commit Protocol

### Step 1: Inspect Working Tree
Always verify what files are modified and untracked:
```powershell
git status -s
```

### Step 2: Strict Exclusion Rules
> [!CAUTION]
> **NEVER** stage or commit:
> * `.env` or `.env.*` (Contains database passwords, session keys, and live API credentials)
> * `storage/framework/views/*` (Compiled view cache files)
> * `storage/logs/*` or temporary logs
> * Personal scratch files or raw database dumps

### Step 3: Stage Verified Code Files
Stage only legitimate application changes:
```powershell
git add app/ resources/ routes/ tests/ public/build/ database/ TEAM_SETUP_GUIDE.md Start-NgrokMcare.ps1 .agents/
```

### Step 4: Craft a Clean Commit Message
Follow Conventional Commits format (`feat:`, `fix:`, `refactor:`, `test:`, `chore:`):
```powershell
git commit -m "feat: implement trainee paymongo checkout, unified quiz results, and account deletion fixes"
```

---

## 🚀 Push Protocol

### Step 1: Check Current Branch
Verify the active branch matches the intended destination:
```powershell
git branch --show-current
```

### Step 2: Optional Dry Run
Check for upstream conflicts without actually pushing:
```powershell
git push --dry-run origin <branch-name>
```

### Step 3: Execute Push
```powershell
git push origin <branch-name>
```

---

## 🌐 Local vs. Deployed Environment Quick-Switch

When switching between local development and Cloudflare Tunnel / Production:

| Setting | Local Development | Cloudflare Tunnel / Production |
| :--- | :--- | :--- |
| `APP_URL` | `http://127.0.0.1:8000` | `https://your-tunnel.trycloudflare.com` |
| `APP_DEBUG` | `true` | `false` |
| `SESSION_SECURE_COOKIE` | `false` | `true` |
| `TRUSTED_PROXIES` | `null` | `127.0.0.1,::1` |

After modifying `.env`, always run:
```powershell
php artisan config:clear
```
