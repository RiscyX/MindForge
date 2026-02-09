# Authentication Decision (Web + Mobile)

## Goal
This document defines how authentication works in the MindForge project across:
- Web app (CakePHP)
- Mobile app (React Native)

The purpose is to prevent confusion during development and ensure consistent security rules.

---

## Web Authentication (CakePHP)

### Mechanism
- **Session-based authentication**
- Uses CakePHP’s Authentication stack with server-side sessions.
- Authentication state is stored in the session (cookie on the client, session data on the server).

### Cookies
- Session cookie should be:
    - **HttpOnly** (not accessible from JavaScript)
    - **Secure** in production (HTTPS only)
    - **SameSite=Lax** (default is fine for typical web flows)

### CSRF
- **CSRF protection is enabled** for all browser-based forms and state-changing requests.
- Web forms must be rendered using CakePHP FormHelper or otherwise include the CSRF token properly.

### Account states
Web login must enforce these rules:
- Users cannot log in if they are **inactive** (not activated).
- Users cannot log in if they are **banned/blocked**.
- Passwords are stored using **bcrypt hashing**.

### Logout rules
- Logout clears the session on the server side.
- The session cookie becomes invalid after logout.

---

## Mobile Authentication (React Native)

### Mechanism
- **Token-based authentication**
- The mobile app does not rely on browser session cookies.
- The mobile app uses:
    - a **short-lived access token**
    - a **long-lived refresh token** (used to restore sessions without requiring login every time)

### Token lifetimes
Recommended defaults:
- **Access token**: ~15 minutes
- **Refresh token**: 7–30 days (project decision; shorter is more secure, longer is more convenient)

### Token storage (security requirement)
Mobile tokens must be stored in **secure OS storage**:
- Expo: SecureStore
- Bare React Native: Keychain (iOS) / Keystore (Android)

**Do not store tokens in AsyncStorage.**

### Session restoration
- The mobile app may restore an authenticated state using the refresh token after:
    - app restart
    - device reboot
    - temporary network loss

### Logout rules
- Logout must invalidate the long-lived token so the mobile session cannot be restored.
- The short-lived token expires naturally after its lifetime.
- API logout supports current-device revoke by default and optional all-device revoke via `all_devices=true`.

### API token persistence (server-side)
- Access and refresh tokens are both stored server-side in hashed form.
- Each token has a random public `token_id` (audit-safe identifier) and a secret part known only by the client.
- Refresh token rotation is enforced on every refresh.
- Refresh token reuse triggers family-wide revocation as a security signal.

---

## Shared Security Rules (Web + Mobile)

### Password policy
- Enforce minimum password length.
- Store only bcrypt-hashed passwords.
- Never log raw passwords.

### Activation + Reset tokens
- Activation and password reset tokens are:
    - single-use
    - time-limited (expire)
    - stored server-side in a dedicated tokens table

### Logging (recommended baseline)
Security-sensitive actions should be logged:
- failed login attempts
- successful logins
- activation events
- password reset events
- logout events
- role changes / bans (admin actions)

### Operational cleanup
- Expired or long-revoked API tokens should be cleaned up regularly.
- Use `bin/cake api_tokens_cleanup --retention-days 30` from cron/scheduler.

---

## Decision Summary
- **Web**: session-based authentication with CSRF protection.
- **Mobile**: token-based authentication (short-lived access + long-lived refresh).
- Both platforms enforce: inactive/banned rules, bcrypt passwords, and token expiry rules.
