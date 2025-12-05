---
title: Introduction
nav_order: 1
layout: default
---

# Introduction

PHP Script is a scripting language designed to let you empower your end-users. It offers a way to safely run user-provided code in a sandboxed environment within your PHP backend. This means you can allow users to customize parts of your application's logic without compromising security.

## Why PHP Script?

In modern applications, especially SaaS platforms, there's a growing need for user-specific customizations. Whether it's for custom validation rules, dynamic pricing models, or unique workflow triggers, developers often face a choice:

1.  **Build a complex GUI for every possible customization:** This is time-consuming, hard to maintain, and can never cover all edge cases.
2.  **Let users provide code in a full-fledged language:** This is a huge security risk. Languages like PHP or Python are too powerful and dangerous to be executed directly from user input.
3.  **Use a separate service for sandboxed execution:** This adds complexity to your architecture, increases latency, and introduces another point of failure.

PHP Script provides a fourth option: a simple, secure, and lightweight scripting language that runs directly within your PHP environment.

## The Philosophy

PHP Script is built on a few core principles:

-   **Safety First:** The language is designed to be sandboxed from the ground up. You have full control over what functions and data are exposed to the script.
-   **Simplicity:** The syntax is heavily inspired by JavaScript, making it familiar to a wide range of developers and easy to learn for non-developers.
-   **Flexibility:** You can expose any PHP function or variable to the script, allowing for powerful and flexible customizations.
-   **Zero External Dependencies:** The core engine is written in pure PHP and has no external dependencies, making it easy to integrate into any project.

By embedding PHP Script in your application, you can give your users the power to create their own solutions, tailored to their specific needs, without compromising the security and integrity of your system.
