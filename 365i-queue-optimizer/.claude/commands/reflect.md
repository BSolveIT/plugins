# Reflect and Learn

Analyze recent session diaries and update CLAUDE.md with learned patterns.

## Instructions

### Step 1: Read Recent Diaries
Read all diary files from `.claude/sessions/diary-*.md` (focus on the last 5-10 entries).

### Step 2: Identify Recurring Patterns
Look for patterns that appear 2 or more times across diaries:

**WordPress Patterns:**
- Security patterns consistently applied
- Hook patterns frequently used
- Database access patterns
- Options/settings patterns
- Conditional loading patterns

**Code Organization:**
- File structure decisions
- Code separation patterns (admin/public/includes)
- Naming conventions
- File size management (keeping under 300 lines)

**Development Workflow:**
- Testing approaches that worked
- Debugging techniques
- Release processes

**User Preferences:**
- Coding style preferences
- Tool preferences
- Things to avoid

### Step 3: Update CLAUDE.md
Update `.claude/CLAUDE.md` with discovered patterns:

1. **Add new patterns** to the appropriate section
2. **Strengthen existing patterns** that are reinforced by diaries
3. **Remove or update patterns** that diaries show aren't working
4. **Keep it concise** - only high-signal, actionable patterns
5. **Include examples** where helpful

### Step 4: Archive Processed Diaries (Optional)
Consider noting which diaries have been processed to avoid re-processing.

## Pattern Format for CLAUDE.md

Use this format when adding patterns:

```markdown
### [Pattern Name]
**When**: Describe when to apply this pattern
**Why**: Brief rationale
**How**:
```php
// Code example if applicable
```
**Learned from**: diary-YYYYMMDD-HHMMSS.md
```

## Quality Checks

Before finalizing CLAUDE.md updates:
- [ ] Patterns are specific and actionable
- [ ] No duplicate or conflicting patterns
- [ ] WordPress Plugin Handbook compliance maintained
- [ ] Code examples follow WordPress coding standards
- [ ] File stays focused and readable

## Summary

After completing the reflection, provide:
1. Number of diaries analyzed
2. New patterns added to CLAUDE.md
3. Patterns updated or strengthened
4. Any patterns removed
5. Recommendations for next session
