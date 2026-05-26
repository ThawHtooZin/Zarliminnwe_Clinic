# Code Review Skill
## Rules
- Check for unused variables
- Flag missing error handling
- Suggest early returns to reduce nesting
- Keep comments short, one line per issue

## Format
[FILE:LINE] issue — suggestion
Example: [auth.go:42] no error check — add `if err != nil { return err }`
