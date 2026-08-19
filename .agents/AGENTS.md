# AGENT GUIDELINES & RULES

## 🛑 Strict Git Push Policy

> **CRITICAL RULE**: The AI agent **MUST NOT** perform any `git push` or push code to remote repositories without receiving **explicit permission** from the user.

### Execution Rules:
1. **Never auto-push**: Do not execute `git push`, `git push origin`, or any remote push commands automatically.
2. **Explicit Consent Required**: Always ask the user for permission and receive clear confirmation before running any git push operation.
3. **Commit Restrictions**: Even when committing changes locally, do not push changes upstream unless instructed to do so.
4. **No immediate push suggestions**: Commit the changes locally when work is completed, but do not ask to push immediately. Save all pushes for the end of the day or when explicitly requested by the user.

## 🛑 Strict Live Server & .env Protection Policy

> **CRITICAL RULE**: 
> 1. **Never alter live DB or URL settings**: Never modify, replace, or overwrite `APP_URL`, `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, or payment gateway variables on the live production server.
> 2. **Targeted updates only**: When configuring mail/SMTP or service credentials, only update the specific targeted configuration lines without replacing the live environment file.
> 3. **Never push .env to Git**: Ensure `.env` is strictly local and never committed or pushed to Git repositories.
