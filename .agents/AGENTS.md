# AGENT GUIDELINES & RULES

## 🛑 Strict Git Push Policy

> **CRITICAL RULE**: The AI agent **MUST NOT** perform any `git push` or push code to remote repositories without receiving **explicit permission** from the user.

### Execution Rules:
1. **Never auto-push**: Do not execute `git push`, `git push origin`, or any remote push commands automatically.
2. **Explicit Consent Required**: Always ask the user for permission and receive clear confirmation before running any git push operation.
3. **Commit Restrictions**: Even when committing changes locally, do not push changes upstream unless instructed to do so.
