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

Call functions or methods with arguments.

```javascript
echo 'Hello World!'; // Calling a global function (if exposed)
user.hasPermission('admin'); // Calling a method on an object
```
