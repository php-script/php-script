---
title: Object and Array Interaction
parent: PHP Script Language Reference
nav_order: 4
layout: default
---

# Object and Array Interaction
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Member Access

Access properties or methods of an object using the `.` operator.

```javascript
userName = user.name;
loginCount = user.logins.count();
```

## Array Access

Access elements of an array using square brackets `[]`.

```javascript
firstUser = users_list[0];
secondUser = users_list[1];
```

## Function Calls

Call functions or methods with arguments. Be careful what functions do you provide by the 
[whitelist](../php/functions.html).

```javascript
echo 'Hello World!'; // echo is provided as script functionality
user.hasPermission('admin'); // Calling a method on an object

echo strtoupper('foo') // Calling a global function (if exposed)
```
