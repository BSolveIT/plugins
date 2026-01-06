#!/usr/bin/env python3
"""
PostToolUse Hook: Capture outcomes from tool executions.

Logs bash command failures, file edits, and errors to session files
for later learning and pattern extraction.
"""

import json
import os
import sys
from datetime import datetime
from pathlib import Path

def get_session_file():
    """Get the session log file path for today."""
    project_root = Path(__file__).parent.parent.parent
    sessions_dir = project_root / ".claude" / "sessions"
    sessions_dir.mkdir(parents=True, exist_ok=True)

    today = datetime.now().strftime("%Y-%m-%d")
    return sessions_dir / f"{today}.jsonl"

def log_outcome(entry: dict):
    """Append an entry to the session log."""
    session_file = get_session_file()
    entry["timestamp"] = datetime.now().isoformat()

    with open(session_file, "a", encoding="utf-8") as f:
        f.write(json.dumps(entry) + "\n")

def main():
    # Read hook input from stdin
    try:
        hook_input = json.load(sys.stdin)
    except json.JSONDecodeError:
        sys.exit(0)  # Exit cleanly if no valid input

    tool_name = hook_input.get("tool_name", "")
    tool_input = hook_input.get("tool_input", {})
    tool_output = hook_input.get("tool_output", {})

    # Determine if this is worth logging
    entry = {
        "tool": tool_name,
        "session_id": hook_input.get("session_id", "unknown"),
    }

    should_log = False

    if tool_name == "Bash":
        command = tool_input.get("command", "")
        stdout = tool_output.get("stdout", "")
        stderr = tool_output.get("stderr", "")
        exit_code = tool_output.get("exit_code", 0)

        entry["command"] = command[:500]  # Truncate long commands

        # Log failures
        if exit_code != 0:
            entry["outcome"] = "failure"
            entry["exit_code"] = exit_code
            entry["error"] = (stderr or stdout)[:1000]
            should_log = True

        # Log successful git operations (learning commits, etc.)
        elif any(cmd in command for cmd in ["git commit", "git push", "7z a"]):
            entry["outcome"] = "success"
            entry["output"] = stdout[:500]
            should_log = True

    elif tool_name in ("Edit", "MultiEdit"):
        file_path = tool_input.get("file_path", "")
        entry["file"] = file_path

        # Check for errors in output
        error = tool_output.get("error")
        if error:
            entry["outcome"] = "failure"
            entry["error"] = str(error)[:500]
            should_log = True
        else:
            # Log edits to key files
            key_files = ["settings.php", "admin-bar.php", "helpers.php", "detection.php"]
            if any(kf in file_path for kf in key_files):
                entry["outcome"] = "success"
                entry["old_string"] = tool_input.get("old_string", "")[:200]
                entry["new_string"] = tool_input.get("new_string", "")[:200]
                should_log = True

    elif tool_name == "Write":
        file_path = tool_input.get("file_path", "")
        entry["file"] = file_path

        error = tool_output.get("error")
        if error:
            entry["outcome"] = "failure"
            entry["error"] = str(error)[:500]
            should_log = True
        elif file_path.endswith((".php", ".js", ".css")):
            entry["outcome"] = "success"
            content = tool_input.get("content", "")
            entry["lines"] = content.count("\n") + 1
            should_log = True

    # Log WordPress-specific patterns
    if should_log:
        content = str(tool_input)

        # Detect WordPress patterns used
        wp_patterns = []
        if "esc_html" in content or "esc_attr" in content:
            wp_patterns.append("output_escaping")
        if "wp_nonce" in content or "check_admin_referer" in content:
            wp_patterns.append("nonce_verification")
        if "current_user_can" in content:
            wp_patterns.append("capability_check")
        if "sanitize_" in content:
            wp_patterns.append("input_sanitization")
        if "is_admin()" in content:
            wp_patterns.append("conditional_loading")
        if "add_action" in content or "add_filter" in content:
            wp_patterns.append("hooks")

        if wp_patterns:
            entry["wp_patterns"] = wp_patterns

        log_outcome(entry)

    # Always exit 0 to not block execution
    sys.exit(0)

if __name__ == "__main__":
    main()
