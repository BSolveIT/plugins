#!/usr/bin/env python3
"""
PostToolUse Hook: Capture Outcomes
Logs bash command failures, file edits, and errors for learning.
Reference: https://developer.wordpress.org/plugins/
"""

import json
import os
import sys
from datetime import datetime
from pathlib import Path

def main():
    # Read hook input from stdin
    try:
        hook_input = json.load(sys.stdin)
    except json.JSONDecodeError:
        sys.exit(0)  # Don't block on parse errors

    tool_name = hook_input.get("tool_name", "")
    tool_input = hook_input.get("tool_input", {})
    tool_result = hook_input.get("tool_result", {})

    # Determine session file path
    project_root = Path(__file__).parent.parent.parent
    sessions_dir = project_root / ".claude" / "sessions"
    sessions_dir.mkdir(parents=True, exist_ok=True)

    today = datetime.now().strftime("%Y-%m-%d")
    session_file = sessions_dir / f"{today}.jsonl"

    # Build log entry
    entry = {
        "timestamp": datetime.now().isoformat(),
        "tool": tool_name,
        "outcome": "unknown"
    }

    # Analyze outcomes based on tool type
    if tool_name == "Bash":
        command = tool_input.get("command", "")
        entry["command"] = command[:500]  # Truncate long commands

        # Check for errors in result
        result_content = str(tool_result)
        if "error" in result_content.lower() or "failed" in result_content.lower():
            entry["outcome"] = "error"
            entry["error_snippet"] = result_content[:1000]
        elif "exit code" in result_content.lower() and "exit code 0" not in result_content.lower():
            entry["outcome"] = "failure"
            entry["error_snippet"] = result_content[:1000]
        else:
            entry["outcome"] = "success"

    elif tool_name in ["Edit", "Write", "MultiEdit"]:
        file_path = tool_input.get("file_path", "")
        entry["file"] = file_path

        result_content = str(tool_result)
        if "error" in result_content.lower() or "failed" in result_content.lower():
            entry["outcome"] = "error"
            entry["error_snippet"] = result_content[:500]
        else:
            entry["outcome"] = "success"

            # Track WordPress-specific patterns
            if file_path.endswith(".php"):
                entry["wp_file"] = True
                # Check for common WordPress patterns in the edit
                new_content = tool_input.get("new_string", "") or tool_input.get("content", "")
                patterns_found = []

                if "sanitize_" in new_content:
                    patterns_found.append("input_sanitization")
                if "esc_" in new_content:
                    patterns_found.append("output_escaping")
                if "wp_nonce" in new_content or "check_admin_referer" in new_content:
                    patterns_found.append("nonce_verification")
                if "current_user_can" in new_content:
                    patterns_found.append("capability_check")
                if "is_admin()" in new_content:
                    patterns_found.append("admin_conditional")

                if patterns_found:
                    entry["wp_patterns"] = patterns_found

    # Only log interesting events (errors, WordPress patterns, or file changes)
    should_log = (
        entry["outcome"] in ["error", "failure"] or
        entry.get("wp_patterns") or
        tool_name in ["Edit", "Write", "MultiEdit"]
    )

    if should_log:
        try:
            with open(session_file, "a", encoding="utf-8") as f:
                f.write(json.dumps(entry) + "\n")
        except Exception:
            pass  # Don't fail on write errors

    # Always exit 0 to not block execution
    sys.exit(0)

if __name__ == "__main__":
    main()
