# Tasks: Break and Continue Keywords

**Input**: Design documents from `/specs/001-break-continue/`
**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, quickstart.md ✅

**Tests**: Constitution requires 100% test coverage - all test tasks are MANDATORY per project quality gates

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

Single project structure at repository root:
- Source: `src/Ast/`, `src/Core/`, `src/Monarch/`
- Tests: `tests/Ast/`, `tests/Core/`, `tests/Monarch/`
- Docs: `docs/language/`, `docs/php/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: No setup needed - using existing PHP Script project structure

✅ **Checkpoint**: Project structure already exists, proceeding to foundational phase

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Add token types and prepare infrastructure for break/continue keywords

**⚠️ CRITICAL**: These changes are required before any user story work can begin

- [ ] T001 Add T_BREAK and T_CONTINUE token type enum cases to src/Core/TokenType.php
- [ ] T002 [P] Add 'break' keyword mapping to TokenType::T_BREAK in src/Core/Lexer.php KEYWORDS array
- [ ] T003 [P] Add 'continue' keyword mapping to TokenType::T_CONTINUE in src/Core/Lexer.php KEYWORDS array
- [ ] T004 Add $loopDepth property (private int $loopDepth = 0) to src/Core/AstTraverser.php
- [ ] T005 [P] Add enterLoop() method to increment $loopDepth in src/Core/AstTraverser.php
- [ ] T006 [P] Add exitLoop() method to decrement $loopDepth in src/Core/AstTraverser.php
- [ ] T007 Write test for 'break' keyword tokenization in tests/Core/LexerTest.php
- [ ] T008 Write test for 'continue' keyword tokenization in tests/Core/LexerTest.php
- [ ] T009 Write test that 'breakpoint' identifier is not tokenized as 'break' in tests/Core/LexerTest.php
- [ ] T010 Write test that 'continuous' identifier is not tokenized as 'continue' in tests/Core/LexerTest.php
- [ ] T011 Run composer lint to fix code style for Lexer changes
- [ ] T012 Run composer test to verify lexer tests pass with 100% coverage

**Checkpoint**: Foundation ready - token types and loop tracking infrastructure in place. User story implementation can now begin.

---

## Phase 3: User Story 1 - Early Exit from Loop (Priority: P1) 🎯 MVP

**Goal**: Enable developers to exit loops early using `break` keyword, eliminating need for boolean flag workarounds

**Independent Test**: Write a for/foreach/while loop with a break statement inside a conditional, execute it, and verify loop terminates at the correct iteration

### AST Node & Tests for User Story 1

- [ ] T013 [P] [US1] Create BreakStatement AST node class in src/Ast/BreakStatement.php with level property (default: 1)
- [ ] T014 [P] [US1] Write test for BreakStatement constructor with default level in tests/Ast/BreakStatementTest.php
- [ ] T015 [P] [US1] Write test for BreakStatement constructor with explicit level in tests/Ast/BreakStatementTest.php
- [ ] T016 [P] [US1] Write test for BreakStatement getLevel() method in tests/Ast/BreakStatementTest.php
- [ ] T017 [P] [US1] Write test for BreakStatement accept() method in tests/Ast/BreakStatementTest.php

### Parser Implementation for User Story 1

- [ ] T018 [US1] Add parseBreakStatement() method to src/Core/Parser.php to handle break keyword and optional level
- [ ] T019 [US1] Add TokenType::T_BREAK case to parseStatement() match in src/Core/Parser.php
- [ ] T020 [US1] Add parse-time validation for break level (reject 0, negative, non-integer) in parseBreakStatement()
- [ ] T021 [P] [US1] Write test for parsing 'break' without level in tests/Core/ParserTest.php
- [ ] T022 [P] [US1] Write test for parsing 'break 1', 'break 2', 'break 3' in tests/Core/ParserTest.php
- [ ] T023 [P] [US1] Write test for parse error on 'break 0' in tests/Core/ParserTest.php
- [ ] T024 [P] [US1] Write test for parse error on 'break -1' in tests/Core/ParserTest.php
- [ ] T025 [P] [US1] Write test for parse error on 'break foo' (non-numeric) in tests/Core/ParserTest.php
- [ ] T026 [P] [US1] Write test for parse error on 'break 2.5' (float not allowed) in tests/Core/ParserTest.php

### AstTraverser Implementation for User Story 1

- [ ] T027 [US1] Add visitBreakStatement() method to src/Core/AstTraverser.php to validate level and generate PHP break code
- [ ] T028 [US1] Modify visitForStatement() to call enterLoop() before and exitLoop() after loop body in src/Core/AstTraverser.php
- [ ] T029 [US1] Modify visitForeachStatement() to call enterLoop() before and exitLoop() after loop body in src/Core/AstTraverser.php
- [ ] T030 [US1] Modify visitWhileStatement() to call enterLoop() before and exitLoop() after loop body in src/Core/AstTraverser.php (if while loops exist)
- [ ] T031 [P] [US1] Write test for break execution in for loop in tests/Core/AstTraverserTest.php
- [ ] T032 [P] [US1] Write test for break execution in foreach loop in tests/Core/AstTraverserTest.php
- [ ] T033 [P] [US1] Write test for break execution in while loop in tests/Core/AstTraverserTest.php (if applicable)
- [ ] T034 [P] [US1] Write test for error when break used outside loop context in tests/Core/AstTraverserTest.php
- [ ] T035 [P] [US1] Write test for error when break level exceeds available loops in tests/Core/AstTraverserTest.php
- [ ] T036 [P] [US1] Write test that break in function call doesn't affect outer loop in tests/Core/AstTraverserTest.php

### PhpScriptRenderer Implementation for User Story 1

- [ ] T037 [US1] Add visitBreakStatement() method to src/Core/PhpScriptRenderer.php to render break back to source code
- [ ] T038 [P] [US1] Write test for rendering 'break' statement in tests/Core/PhpScriptRendererTest.php
- [ ] T039 [P] [US1] Write test for rendering 'break 2' statement in tests/Core/PhpScriptRendererTest.php
- [ ] T040 [P] [US1] Write round-trip test (code → AST → code produces same output) for break in tests/Core/PhpScriptRendererTest.php

### Quality Assurance for User Story 1

- [ ] T041 [US1] Run composer lint to fix code style for all User Story 1 changes
- [ ] T042 [US1] Run composer refactor to apply automated refactorings
- [ ] T043 [US1] Run composer lint again to re-check style after refactoring
- [ ] T044 [US1] Run composer test to verify all tests pass with 100% coverage for break functionality
- [ ] T045 [US1] Verify zero PHPStan errors for break implementation
- [ ] T046 [US1] Verify zero linting errors for break implementation
- [ ] T047 [US1] Verify zero Rector violations for break implementation

**Checkpoint**: User Story 1 (break keyword) is fully functional and independently testable. All acceptance scenarios pass:
- ✅ For loop with break terminates at correct iteration
- ✅ Foreach loop with break exits when target found
- ✅ While loop with break exits on condition
- ✅ Break outside loop produces clear error message
- ✅ Break with invalid level produces clear error message

---

## Phase 4: User Story 2 - Skip Current Iteration (Priority: P2)

**Goal**: Enable developers to skip loop iterations using `continue` keyword for cleaner filtering logic without nested conditionals

**Independent Test**: Write a for/foreach/while loop with a continue statement inside a conditional, verify only specific iterations are skipped while loop continues

### AST Node & Tests for User Story 2

- [ ] T048 [P] [US2] Create ContinueStatement AST node class in src/Ast/ContinueStatement.php with level property (default: 1)
- [ ] T049 [P] [US2] Write test for ContinueStatement constructor with default level in tests/Ast/ContinueStatementTest.php
- [ ] T050 [P] [US2] Write test for ContinueStatement constructor with explicit level in tests/Ast/ContinueStatementTest.php
- [ ] T051 [P] [US2] Write test for ContinueStatement getLevel() method in tests/Ast/ContinueStatementTest.php
- [ ] T052 [P] [US2] Write test for ContinueStatement accept() method in tests/Ast/ContinueStatementTest.php

### Parser Implementation for User Story 2

- [ ] T053 [US2] Add parseContinueStatement() method to src/Core/Parser.php to handle continue keyword and optional level
- [ ] T054 [US2] Add TokenType::T_CONTINUE case to parseStatement() match in src/Core/Parser.php
- [ ] T055 [US2] Add parse-time validation for continue level (reject 0, negative, non-integer) in parseContinueStatement()
- [ ] T056 [P] [US2] Write test for parsing 'continue' without level in tests/Core/ParserTest.php
- [ ] T057 [P] [US2] Write test for parsing 'continue 1', 'continue 2', 'continue 3' in tests/Core/ParserTest.php
- [ ] T058 [P] [US2] Write test for parse error on 'continue 0' in tests/Core/ParserTest.php
- [ ] T059 [P] [US2] Write test for parse error on 'continue -1' in tests/Core/ParserTest.php
- [ ] T060 [P] [US2] Write test for parse error on 'continue foo' (non-numeric) in tests/Core/ParserTest.php
- [ ] T061 [P] [US2] Write test for parse error on 'continue 2.5' (float not allowed) in tests/Core/ParserTest.php

### AstTraverser Implementation for User Story 2

- [ ] T062 [US2] Add visitContinueStatement() method to src/Core/AstTraverser.php to validate level and generate PHP continue code
- [ ] T063 [P] [US2] Write test for continue execution in for loop in tests/Core/AstTraverserTest.php
- [ ] T064 [P] [US2] Write test for continue execution in foreach loop in tests/Core/AstTraverserTest.php
- [ ] T065 [P] [US2] Write test for continue execution in while loop in tests/Core/AstTraverserTest.php (if applicable)
- [ ] T066 [P] [US2] Write test for error when continue used outside loop context in tests/Core/AstTraverserTest.php
- [ ] T067 [P] [US2] Write test for error when continue level exceeds available loops in tests/Core/AstTraverserTest.php
- [ ] T068 [P] [US2] Write test that continue in function call doesn't affect outer loop in tests/Core/AstTraverserTest.php

### PhpScriptRenderer Implementation for User Story 2

- [ ] T069 [US2] Add visitContinueStatement() method to src/Core/PhpScriptRenderer.php to render continue back to source code
- [ ] T070 [P] [US2] Write test for rendering 'continue' statement in tests/Core/PhpScriptRendererTest.php
- [ ] T071 [P] [US2] Write test for rendering 'continue 3' statement in tests/Core/PhpScriptRendererTest.php
- [ ] T072 [P] [US2] Write round-trip test (code → AST → code produces same output) for continue in tests/Core/PhpScriptRendererTest.php

### Quality Assurance for User Story 2

- [ ] T073 [US2] Run composer lint to fix code style for all User Story 2 changes
- [ ] T074 [US2] Run composer refactor to apply automated refactorings
- [ ] T075 [US2] Run composer lint again to re-check style after refactoring
- [ ] T076 [US2] Run composer test to verify all tests pass with 100% coverage for continue functionality
- [ ] T077 [US2] Verify zero PHPStan errors for continue implementation
- [ ] T078 [US2] Verify zero linting errors for continue implementation
- [ ] T079 [US2] Verify zero Rector violations for continue implementation

**Checkpoint**: User Story 2 (continue keyword) is fully functional and independently testable. All acceptance scenarios pass:
- ✅ For loop with continue skips even numbers, processes odd numbers
- ✅ Foreach loop with continue skips invalid records, processes valid ones
- ✅ While loop with continue skips empty items
- ✅ Continue outside loop produces clear error message
- ✅ Continue with invalid level produces clear error message

---

## Phase 5: User Story 3 - Nested Loop Control (Priority: P3)

**Goal**: Enable developers to control multiple loop levels using numeric parameters like `break 2` or `continue 3` for cleaner nested loop code

**Independent Test**: Create nested for/foreach loops, use `break 2` to exit both loops or `continue 2` to skip outer loop iteration, verify control flow behaves correctly

### Integration Tests for User Story 3

- [ ] T080 [P] [US3] Write test for `break 2` exiting two nested for loops in tests/Core/AstTraverserTest.php
- [ ] T081 [P] [US3] Write test for `continue 2` continuing outer loop from nested foreach in tests/Core/AstTraverserTest.php
- [ ] T082 [P] [US3] Write test for `break 3` exiting three-level nested loops in tests/Core/AstTraverserTest.php
- [ ] T083 [P] [US3] Write test for mixed loop types (for inside foreach) with level parameters in tests/Core/AstTraverserTest.php
- [ ] T084 [P] [US3] Write test verifying break/continue level validation with various nesting depths in tests/Core/AstTraverserTest.php

### Quality Assurance for User Story 3

- [ ] T085 [US3] Run composer test to verify nested loop control tests pass with 100% coverage
- [ ] T086 [US3] Verify all edge cases from spec are covered by tests
- [ ] T087 [US3] Verify zero PHPStan errors for complete break/continue implementation
- [ ] T088 [US3] Verify zero linting errors for complete implementation
- [ ] T089 [US3] Verify zero Rector violations for complete implementation

**Checkpoint**: User Story 3 (nested loop control) is fully functional and independently testable. All acceptance scenarios pass:
- ✅ `break 2` in nested loops exits both loops correctly
- ✅ `continue 2` in nested loops continues outer loop correctly
- ✅ `break 3` in three-level nested loops exits all three
- ✅ Invalid level for available loops produces clear error

---

## Phase 6: Monaco Editor Integration (Cross-Cutting)

**Purpose**: Add break/continue keywords to Monaco Editor for syntax highlighting and code completion

- [ ] T090 [P] Add 'break' to KEYWORDS array in src/Monarch/MonarchLanguageDefinitionService.php
- [ ] T091 [P] Add 'continue' to KEYWORDS array in src/Monarch/MonarchLanguageDefinitionService.php
- [ ] T092 [P] Add code completion snippet for 'break' in src/Monarch/MonarchLanguageDefinitionService.php
- [ ] T093 [P] Add code completion snippet for 'continue' in src/Monarch/MonarchLanguageDefinitionService.php
- [ ] T094 [P] Add code completion snippet for 'break 2' (nested variant) in src/Monarch/MonarchLanguageDefinitionService.php
- [ ] T095 [P] Add code completion snippet for 'continue 2' (nested variant) in src/Monarch/MonarchLanguageDefinitionService.php
- [ ] T096 [P] Write test verifying 'break' is in keywords list in tests/Monarch/MonarchLanguageDefinitionServiceTest.php
- [ ] T097 [P] Write test verifying 'continue' is in keywords list in tests/Monarch/MonarchLanguageDefinitionServiceTest.php
- [ ] T098 [P] Write test verifying break/continue snippets are included in tests/Monarch/MonarchLanguageDefinitionServiceTest.php
- [ ] T099 [P] Write test verifying snippets have correct format in tests/Monarch/MonarchLanguageDefinitionServiceTest.php
- [ ] T100 Run composer lint to fix code style for Monaco changes
- [ ] T101 Run composer test to verify Monaco tests pass with 100% coverage

**Checkpoint**: Monaco Editor integration complete:
- ✅ Break and continue highlighted as keywords
- ✅ Code completion suggests break/continue with snippets
- ✅ Tests verify Monaco integration working correctly

---

## Phase 7: Documentation Updates (Cross-Cutting)

**Purpose**: Update language documentation to include break/continue keywords per user request to update docs/language/ in detail

### Primary Documentation

- [ ] T102 [P] Update docs/language/control-flow.md to add "Loop Control" section after existing loop documentation
- [ ] T103 [P] Add break keyword documentation with examples to docs/language/control-flow.md
- [ ] T104 [P] Add continue keyword documentation with examples to docs/language/control-flow.md
- [ ] T105 [P] Add nested loop control (level parameters) documentation to docs/language/control-flow.md
- [ ] T106 [P] Add before/after examples showing break/continue vs. flag variables to docs/language/control-flow.md
- [ ] T107 [P] Add error scenario examples (break outside loop, invalid level) to docs/language/control-flow.md
- [ ] T108 [P] Remove note about break not being supported (if exists on line 54) from docs/language/control-flow.md

### Statement Reference

- [ ] T109 [P] Add 'break' to statement list in docs/language/statements.md with description
- [ ] T110 [P] Add 'continue' to statement list in docs/language/statements.md with description
- [ ] T111 [P] Add link from statements.md to control-flow.md for detailed break/continue examples

### Index and Navigation

- [ ] T112 [P] Update language features list in docs/language/index.md to include break/continue
- [ ] T113 [P] Verify navigation links are correct in docs/language/index.md

### Editor Integration Documentation

- [ ] T114 [P] Document new Monaco Editor snippets in docs/php/editor.md
- [ ] T115 [P] Show how code completion suggests break/continue in loops in docs/php/editor.md
- [ ] T116 [P] Document linting behavior for invalid break/continue usage in docs/php/editor.md
- [ ] T117 [P] Add break/continue examples to docs/php/editor.md (if examples section exists)

**Checkpoint**: Documentation complete and published-ready:
- ✅ docs/language/control-flow.md has comprehensive break/continue section
- ✅ docs/language/statements.md lists break/continue
- ✅ docs/language/index.md updated with new features
- ✅ docs/php/editor.md documents Monaco integration
- ✅ All examples are clear and tested
- ✅ Error scenarios documented with expected messages

---

## Phase 8: Polish & Final Quality Assurance

**Purpose**: Final verification that all quality gates pass and feature is production-ready

- [ ] T118 Run full test suite: composer test (verify all 5 quality checks pass)
- [ ] T119 Verify 100% test coverage maintained across all files (composer test:unit with coverage report)
- [ ] T120 Verify 100% type coverage maintained across all files (composer test:type-coverage)
- [ ] T121 Verify zero PHPStan errors at maximum level (composer test:types)
- [ ] T122 Verify zero linting errors PSR-12 compliance (composer test:lint)
- [ ] T123 Verify zero Rector violations (composer test:refactor)
- [ ] T124 Run regression tests: verify all existing loop tests still pass
- [ ] T125 Verify backward compatibility: existing scripts without break/continue still work
- [ ] T126 Test all acceptance scenarios from spec.md manually in playground
- [ ] T127 Verify all edge cases from spec.md are handled with clear error messages
- [ ] T128 Review quickstart.md examples and verify they all work correctly
- [ ] T129 Final code review: check for code smells, unnecessary complexity, or missing documentation
- [ ] T130 Verify CLAUDE.md agent context file is up to date with feature information

**Checkpoint**: Feature complete and ready for PR:
- ✅ All 3 user stories fully functional
- ✅ 100% test coverage achieved
- ✅ 100% type coverage achieved
- ✅ Zero PHPStan/Pint/Rector errors
- ✅ Documentation complete
- ✅ Monaco integration working
- ✅ All acceptance scenarios pass
- ✅ All edge cases handled
- ✅ Backward compatibility maintained

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: ✅ Already complete (existing project structure)
- **Phase 2 (Foundational)**: No dependencies - can start immediately - **BLOCKS all user stories**
- **Phase 3 (User Story 1 - Break)**: Depends on Phase 2 completion
- **Phase 4 (User Story 2 - Continue)**: Depends on Phase 2 completion (independent of User Story 1)
- **Phase 5 (User Story 3 - Nested)**: Depends on Phase 3 AND Phase 4 completion (needs both break and continue)
- **Phase 6 (Monaco Editor)**: Depends on Phase 3 and Phase 4 completion (needs both keywords)
- **Phase 7 (Documentation)**: Can proceed in parallel with Phase 3-6 (no code dependencies)
- **Phase 8 (Polish)**: Depends on ALL previous phases complete

### User Story Dependencies

- **User Story 1 (P1 - Break)**: Independent - Can start after Foundational phase
- **User Story 2 (P2 - Continue)**: Independent - Can start after Foundational phase (parallel with US1)
- **User Story 3 (P3 - Nested)**: Depends on both US1 and US2 (tests both keywords together)

### Critical Path

```
Phase 2 (Foundational)
  → Phase 3 (US1: Break) ─┐
  → Phase 4 (US2: Continue) ─┤
                              ├─→ Phase 5 (US3: Nested) ─┐
  → Phase 7 (Documentation) ──┤                           ├─→ Phase 8 (Polish) → DONE
                              └─→ Phase 6 (Monaco) ───────┘
```

### Within Each User Story

**User Story 1 (Break)**:
1. AST node creation (T013) must precede all tests and implementation
2. AST node tests (T014-T017) can run in parallel [P]
3. Parser implementation (T018-T020) must complete before parser tests
4. Parser tests (T021-T026) can run in parallel [P]
5. AstTraverser implementation (T027-T030) must complete before traverser tests
6. AstTraverser tests (T031-T036) can run in parallel [P]
7. PhpScriptRenderer implementation (T037) before renderer tests
8. Renderer tests (T038-T040) can run in parallel [P]
9. Quality assurance tasks (T041-T047) run sequentially at end

**User Story 2 (Continue)**: Same pattern as User Story 1

**User Story 3 (Nested)**: Only integration tests, all can run in parallel [P] after US1 and US2 complete

### Parallel Opportunities

#### Foundational Phase (Phase 2)
All lexer keyword additions can run in parallel:
- T002 (break keyword) || T003 (continue keyword)
- T005 (enterLoop) || T006 (exitLoop)
- T007-T010 (lexer tests all parallel)

#### After Foundational Complete
User Story 1 and User Story 2 can proceed **completely in parallel** (different developers):
- Developer A: Phase 3 (T013-T047) - Break implementation
- Developer B: Phase 4 (T048-T079) - Continue implementation

#### Within User Stories
- All AST node tests marked [P] can run in parallel
- All parser tests marked [P] can run in parallel
- All traverser tests marked [P] can run in parallel
- All renderer tests marked [P] can run in parallel

#### Documentation (Phase 7)
All documentation tasks marked [P] can run in parallel:
- T102-T108 (control-flow.md updates)
- T109-T111 (statements.md updates)
- T112-T113 (index.md updates)
- T114-T117 (editor.md updates)

---

## Parallel Example: User Story 1

```bash
# After Phase 2 complete, launch User Story 1 AST node and tests in parallel:
Task T013: "Create BreakStatement AST node class in src/Ast/BreakStatement.php"
  Then parallel:
    Task T014: "Test BreakStatement constructor with default level"
    Task T015: "Test BreakStatement constructor with explicit level"
    Task T016: "Test BreakStatement getLevel() method"
    Task T017: "Test BreakStatement accept() method"

# After parser implementation (T018-T020), launch all parser tests in parallel:
Task T021: "Test parsing 'break' without level"
Task T022: "Test parsing 'break 1', 'break 2', 'break 3'"
Task T023: "Test parse error on 'break 0'"
Task T024: "Test parse error on 'break -1'"
Task T025: "Test parse error on 'break foo'"
Task T026: "Test parse error on 'break 2.5'"

# After traverser implementation (T027-T030), launch all traverser tests in parallel:
Task T031: "Test break execution in for loop"
Task T032: "Test break execution in foreach loop"
Task T033: "Test break execution in while loop"
Task T034: "Test error when break used outside loop"
Task T035: "Test error when break level exceeds available loops"
Task T036: "Test break in function call doesn't affect outer loop"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

**Delivers**: Basic `break` keyword functionality - developers can exit loops early

1. Complete Phase 2: Foundational (T001-T012) → ~12 tasks
2. Complete Phase 3: User Story 1 (T013-T047) → ~35 tasks
3. **STOP and VALIDATE**: Test break in for/foreach/while loops
4. Deploy/demo break functionality

**Result**: MVP with break keyword working, ~47 total tasks

### Incremental Delivery (All User Stories)

**Delivers**: Complete break/continue feature with nested loop control

1. Complete Phase 2: Foundational → Foundation ready
2. **Add User Story 1** (T013-T047) → Test independently → Deploy (break works!)
3. **Add User Story 2** (T048-T079) → Test independently → Deploy (continue works!)
4. **Add User Story 3** (T080-T089) → Test independently → Deploy (nested control works!)
5. **Add Monaco** (T090-T101) → Test editor integration → Deploy (highlighting works!)
6. **Add Documentation** (T102-T117) → Review docs → Publish (docs complete!)
7. **Polish** (T118-T130) → Final QA → Production ready

**Result**: Full feature with all user stories, ~130 total tasks

### Parallel Team Strategy

With 2 developers:

1. Team completes Foundational together (T001-T012)
2. Once Foundational done:
   - **Developer A**: User Story 1 - Break (T013-T047)
   - **Developer B**: User Story 2 - Continue (T048-T079)
3. After both US1 and US2 complete:
   - **Developer A**: Monaco Editor (T090-T101)
   - **Developer B**: Documentation (T102-T117)
4. User Story 3 requires both US1 and US2, so either developer (T080-T089)
5. Team completes Polish together (T118-T130)

**Result**: Significantly faster delivery with parallel user stories

---

## Task Summary

### Total Tasks: 130

### Tasks per Phase:
- **Phase 1 (Setup)**: 0 tasks (existing structure)
- **Phase 2 (Foundational)**: 12 tasks (T001-T012)
- **Phase 3 (User Story 1 - Break)**: 35 tasks (T013-T047)
- **Phase 4 (User Story 2 - Continue)**: 32 tasks (T048-T079)
- **Phase 5 (User Story 3 - Nested)**: 10 tasks (T080-T089)
- **Phase 6 (Monaco Editor)**: 12 tasks (T090-T101)
- **Phase 7 (Documentation)**: 16 tasks (T102-T117)
- **Phase 8 (Polish & QA)**: 13 tasks (T118-T130)

### Tasks per User Story:
- **Setup & Foundation**: 12 tasks
- **User Story 1 (P1)**: 35 tasks
- **User Story 2 (P2)**: 32 tasks
- **User Story 3 (P3)**: 10 tasks
- **Cross-Cutting (Monaco + Docs + Polish)**: 41 tasks

### Parallel Opportunities:
- **Foundational**: 6 tasks can run in parallel (T002-T003, T005-T006, T007-T010)
- **User Story 1 vs 2**: Entire phases can run in parallel (67 tasks total)
- **User Story 1 internal**: 15 test tasks marked [P]
- **User Story 2 internal**: 15 test tasks marked [P]
- **User Story 3 internal**: 5 test tasks marked [P]
- **Monaco Editor**: 4 tasks marked [P]
- **Documentation**: 16 tasks marked [P]

### Independent Test Criteria:
- **User Story 1**: Write for/foreach loop with break, verify loop exits at correct iteration ✅
- **User Story 2**: Write for/foreach loop with continue, verify only specific iterations skip ✅
- **User Story 3**: Write nested loops with break 2/continue 2, verify outer loops affected ✅

### Suggested MVP Scope:
**Phase 2 + Phase 3 = ~47 tasks**
- Delivers: Basic `break` keyword functionality
- Value: Developers can exit loops early without flag variables
- Independently testable and deployable

### Format Validation:
✅ All tasks follow checklist format: `- [ ] [ID] [P?] [Story?] Description with file path`
✅ All task IDs sequential (T001-T130)
✅ All user story tasks have [Story] label (US1, US2, US3)
✅ All parallelizable tasks marked with [P]
✅ All tasks include specific file paths
✅ All phases have clear checkpoints and independent test criteria

---

## Notes

- **[P] tasks**: Different files, no dependencies - can run in parallel
- **[Story] label**: Maps task to specific user story for traceability
- **Constitution compliance**: All quality checks (100% coverage, PHPStan, Pint, Rector) enforced
- **Independent testing**: Each user story can be tested without others
- **Incremental delivery**: MVP (break only) → Continue → Nested → Complete
- **Documentation emphasis**: User requested detailed docs/language/ updates - Phase 7 covers this extensively
- **Quality gates**: Each phase ends with comprehensive QA checks
- **Backward compatibility**: Phase 8 includes regression testing to verify existing code still works
