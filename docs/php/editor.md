---
title: Integrate Editor
parent: PHP
nav_order: 7
layout: default
---

# Integrating the Editor
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Monaco Editor

We support out-of-the-box the [Monaco Editor](https://microsoft.github.io/monaco-editor/).

Therefore we can create the whole language definition and code completion suggestion in the Monarch standard for the
Monaco Editor.

Our [Playground](https://github.com/php-script/php-script/blob/main/public/playground.php) provides a base 
implementation of a pre-configured editor with dynamic code completion and the full language definition syntax.

But we also provide a npm package for a pre-configured Monaco editor written in Typescript soon. You can head over to 
the [repository](https://github.com/php-script/php-script-editor) for a first look.

The base of the server-side rendering is built by the `MonarchLanguageDefinitionService` which can create all necessary 
dynamic stuff for the editor.
