# METRA Classifieds – Development Rules

These rules are binding for every future change.

## 1. Delivery
- Always deliver a complete ZIP version.
- Use the exact versioned file name.
- No temporary file names.

## 2. Scope discipline
- Only implement the requested change.
- No silent side effects.
- No unrelated layout changes.

## 3. Compact code blocks
- Everything that belongs together functionally must live in one compact code block.
- Everything that belongs together visually must live in one compact code block.
- Do not scatter one feature across unrelated positions without necessity.

## 4. Documentation in code
Each new block must contain:
- feature name
- version
- purpose
- rollback note

## 5. Rollback safety
- Every change must be clearly identifiable.
- Every change must be easy to remove.

## 6. Readme files
Only keep:
- readme.txt
- README_VERSION.txt
- README_CHANGES.txt

## 7. CSS/UI rules
- Prefer component-based CSS.
- Avoid broad global overrides.
- Keep visual changes isolated and reversible.

## 8. Internationalisation (i18n)
- ALL text visible in the frontend MUST be translatable.
- Use __(), esc_html__(), esc_attr__() with 'metra-classifieds-plugin' as text domain.
- Add every new string to all .po files: de_DE, de_DE_formal, en_US and .pot.
- Admin-area strings can be handled as a separate block but must still be wrapped.
- Never output raw hardcoded strings in frontend templates or render_* methods.
