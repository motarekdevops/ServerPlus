# Contributing to ServerPulse

Thanks for your interest in contributing! This project is free and open source, and contributions of all kinds are welcome — bug fixes, features, documentation improvements, or just reporting issues.

## Getting Started

1. Fork the repository
2. Clone your fork:
```bash
   git clone https://github.com/YOUR_USERNAME/ServerPlus.git
   cd ServerPlus
```
3. Set up the project (see the [README](README.md) for Docker or manual installation)
4. Create a new branch for your change:
```bash
   git checkout -b feature/your-feature-name
```

## Making Changes

- Keep changes focused — one feature or fix per pull request
- Follow the existing code style (PSR-12 for PHP, standard Laravel conventions)
- Add or update tests for any new logic, especially in `app/Services` and `app/Jobs`
- Run the test suite before submitting:
```bash
  php artisan test
```

## Submitting a Pull Request

1. Push your branch to your fork
2. Open a pull request against the `main` branch
3. Describe what your change does and why
4. Link any related issues

CI will automatically run the test suite on your PR. Please make sure it passes before requesting review.

## Reporting Bugs

Open an issue with:
- A clear description of the problem
- Steps to reproduce it
- What you expected to happen vs. what actually happened
- Your environment (OS, PHP version, Docker or manual install)

## Suggesting Features

Open an issue describing the feature and the use case it solves. If it's a larger change, it helps to outline the approach before starting work, so we can align before you invest time in it.

## Code of Conduct

Be respectful and constructive. This is a small project maintained in spare time — patience and clear communication go a long way.

## Questions?

Open an issue or start a discussion. Happy to help.
