---
name: version-aligner
description: Use this skill when checking, diagnosing, or synchronizing an application's version with its Git tag and CHANGELOG.md using version-aligner.
---

# version-aligner

This skill teaches the agent how to use `version-aligner` to keep an application's
version aligned with the release version represented by Git tags and `CHANGELOG.md`.

## Purpose

`version-aligner` is a PHP development tool that detects version inconsistencies
between:

1. The latest Git tag
2. The latest version in `CHANGELOG.md`
3. The version defined by the application

All three sources should represent the same release version.

A typical inconsistency looks like:

    Git tag       v1.4.0
    CHANGELOG.md  1.4.0
    Application   1.3.0

The purpose of this skill is to help the agent detect and safely correct this
kind of release-version mismatch.

## When to use this skill

Use this skill when:

- checking whether an application's version is aligned before a release
- diagnosing a version mismatch
- preparing a PHP application or package for release
- updating an application version to match the current release
- working with Git tags and `CHANGELOG.md`
- adding version validation to Composer scripts, CI, or development workflows
- the user explicitly asks to use `version-aligner`

Do not use this skill as a general-purpose versioning or release-management
workflow. `version-aligner` is specifically concerned with version alignment.

## Repository context

Before making changes, inspect the repository's existing:

- `README.md`
- `composer.json`
- `CHANGELOG.md`
- Git tags
- application entry point
- version-related source code
- Composer scripts
- test configuration
- CI workflows

Pay particular attention to `composer.json` and its `require-dev` section to
understand whether `version-aligner` is already installed and which development
tools are used by the project.

Do not introduce alternative tooling when the repository already has an
established version-checking or release workflow unless the user explicitly asks
for a replacement.

## Installation

If `version-aligner` is not installed and the user wants to use it, install it
as a development dependency:

    composer require --dev stolt/version-aligner

Do not install it automatically merely because a version mismatch is detected.
Installation changes the project's dependency manifest and should be intentional.

## Checking version alignment

The primary diagnostic command is:

    vendor/bin/version-aligner check

If the executable is available globally or through another configured mechanism,
the equivalent command may be:

    version-aligner check

The command compares:

- the latest Git tag
- the latest `CHANGELOG.md` version
- the application version

A successful result means the versions are aligned.

A mismatch should be treated as a release consistency problem, not automatically
as evidence that the application version is wrong.

## JSON output

For machine-readable output, use:

    vendor/bin/version-aligner check --format=json

Prefer JSON output when:

- another tool needs to consume the result
- the agent needs structured diagnostic information
- the command is being integrated into automation

Do not parse human-readable terminal output when structured JSON output is
available and appropriate.

## Aligning the application version

When the application version differs from the release version, the `align`
command can update the application version:

    vendor/bin/version-aligner align

Before modifying files, prefer:

    vendor/bin/version-aligner align --dry-run

Use the dry run to determine:

- which version will be used
- which file is expected to change
- whether the detected release version is correct
- whether the proposed change matches the repository's release state

Only perform the actual alignment after the detected release version has been
verified.

## Safety rules

Never blindly run:

    vendor/bin/version-aligner align

when:

- there is no appropriate Git release tag
- the latest tag appears unrelated to the current release
- `CHANGELOG.md` contains an unreleased section instead of a released version
- the latest changelog entry does not represent the intended release
- the repository is in the middle of preparing a release
- the application version discovery appears incorrect
- the user has not asked for a modification and only requested a check

When the sources disagree, first report the detected versions and determine
which release state the repository is intended to represent.

Do not invent or guess the intended version.

## Release workflow

A safe release-oriented workflow is:

    1. Inspect repository
           ↓
    2. Inspect Git tags
           ↓
    3. Inspect CHANGELOG.md
           ↓
    4. Run version-aligner check
           ↓
    5. Diagnose any mismatch
           ↓
    6. Run align --dry-run if correction is appropriate
           ↓
    7. Review the proposed change
           ↓
    8. Run align
           ↓
    9. Run the project's tests and quality checks
           ↓
    10. Run version-aligner check again

The final check should report that all versions are aligned.

## Version discovery

The application version is project-specific.

Before modifying a version, inspect the implementation and documentation of the
installed `version-aligner` version to understand how it discovers the
application version.

Do not assume that the version is stored in `composer.json` or any particular
PHP class unless the project and `version-aligner` explicitly establish that
convention.

If version discovery fails or identifies the wrong value:

1. Do not manually edit an arbitrary file just to make the check pass.
2. Inspect the version discovery implementation and project structure.
3. Determine where the application actually defines its version.
4. Check whether the repository follows a supported application structure.
5. If the project structure is unsupported, report the discovery limitation.
6. If appropriate, propose an issue or enhancement for `version-aligner`.

## Git tag handling

The Git tag represents the release version.

Commonly, a tag may be:

    v1.2.3

while the changelog and application use:

    1.2.3

Do not treat the optional `v` prefix as a mismatch without verifying how
`version-aligner` normalizes and compares versions.

Always inspect the actual tags rather than assuming the expected release tag.

Useful commands include:

    git tag --list
    git describe --tags --abbrev=0

Do not create, rename, or delete Git tags as part of this skill unless the user
explicitly requests Git release management.

## CHANGELOG handling

Inspect `CHANGELOG.md` before aligning versions.

Pay attention to:

- the newest released version
- an `Unreleased` section
- version headings
- release dates
- pre-release identifiers
- whether the changelog represents the release currently being prepared

Do not automatically interpret an `Unreleased` heading as the release version.

The changelog should provide evidence for the intended release version before
using `align`.

## Pre-release versions

Treat pre-release versions carefully.

Examples include:

    1.0.0-alpha.1
    1.0.0-beta.2
    1.0.0-rc.1

Do not normalize, downgrade, or replace a pre-release version merely to make the
three sources appear equal.

If the project's versioning convention conflicts with the tool's version
handling, report the discrepancy and inspect the tool's supported behavior.

## CI integration

`version-aligner` can be used as a release consistency check.

A Composer script may expose the check:

    {
        "scripts": {
            "version-check": "version-aligner check"
        }
    }

The agent should prefer the project's existing Composer script conventions if
they already exist.

For CI, the desired behavior is generally:

    vendor/bin/version-aligner check

A mismatch should cause the validation step to fail rather than silently
modifying source files.

Do not use `align` in CI unless the workflow explicitly intends CI to modify and
commit repository files.

## Composer integration

When `version-aligner` is installed as a Composer development dependency,
prefer the project's Composer-managed executable:

    vendor/bin/version-aligner

Do not assume a globally installed binary exists.

Before installing a new copy, check:

    composer.json
    composer.lock

for an existing installation.

## Working with existing development tooling

Before adding or changing version-related commands:

1. Read `composer.json`.
2. Inspect `require-dev`.
3. Inspect Composer scripts.
4. Inspect CI workflows.
5. Inspect the existing release documentation.
6. Reuse existing conventions where possible.

Do not introduce duplicate version checks if the project already has an
equivalent mechanism.

## Diagnosing a mismatch

When `check` reports a mismatch, report the three values explicitly:

    Git tag:       v1.4.0
    CHANGELOG.md:  1.4.0
    Application:   1.3.0

Then determine the likely source of the discrepancy from repository evidence.

Possible causes include:

- application version was not bumped
- changelog was updated but application version was forgotten
- a release tag was created prematurely
- the changelog contains a different release
- version discovery found the wrong application value
- the repository is preparing a release rather than representing a completed release

Do not automatically change the application version merely because it is the
only value that differs.

## Modifying files

When `align` changes the application version:

1. Review the diff.
2. Ensure only the expected version-related changes were made.
3. Run the project's tests.
4. Run the project's static analysis and coding-standard tools when applicable.
5. Run `version-aligner check` again.

Useful commands may include:

    git diff
    vendor/bin/version-aligner check

Use the repository's existing test and quality commands rather than inventing
new ones.

## Minimal-change principle

When fixing version alignment:

- change only the required version value
- do not reformat unrelated code
- do not rewrite `CHANGELOG.md` unless requested
- do not create a Git tag
- do not modify release automation unnecessarily
- do not update dependencies unrelated to the version issue

Keep the resulting diff focused and reviewable.

## Adding version alignment to a project

If the user asks how to integrate `version-aligner` into an existing PHP
project:

1. Determine how the application currently stores its version.
2. Verify that `version-aligner` can discover it.
3. Install it with Composer as a development dependency.
4. Run `check`.
5. Add a Composer script if useful.
6. Add CI validation if requested.
7. Document the workflow if it becomes part of the project's release process.

Example:

    {
        "scripts": {
            "version-check": "version-aligner check"
        }
    }

Then:

    composer version-check

## Adding an issue or enhancement

If version discovery fails because the project uses an unsupported structure,
capture enough information to reproduce the problem.

Include:

- operating system
- PHP version
- `version-aligner` version
- relevant project structure
- where the application version is defined
- the command executed
- actual output
- expected behavior
- a minimal reproduction where practical

Do not include secrets, credentials, private repository URLs, or unrelated
project data.

## Agent behavior

When asked to "check the version":

    Run `version-aligner check`.
    Report the three detected versions and whether they are aligned.
    Do not modify files.

When asked to "align the version":

    1. Run the check.
    2. Verify the intended release version.
    3. Run `align --dry-run`.
    4. Review the proposed change.
    5. Run `align`.
    6. Review the diff.
    7. Run tests/quality checks.
    8. Run the final version check.

When asked to "prepare a release":

    Treat version alignment as one release validation step, not as the complete
    release process.

Do not create a release tag, GitHub release, changelog entry, or package
publication unless explicitly requested.

## Example commands

Check:

    vendor/bin/version-aligner check

Check with JSON:

    vendor/bin/version-aligner check --format=json

Preview alignment:

    vendor/bin/version-aligner align --dry-run

Apply alignment:

    vendor/bin/version-aligner align

## Expected outcome

After a successful alignment:

    Git tag       v1.4.0
    CHANGELOG.md  1.4.0
    Application   1.4.0

The goal is not merely to make a command pass. The repository should have a
consistent and intentional release version across all three sources.
