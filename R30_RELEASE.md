# R30

This release removes the shared-layout/rendering path for onboarding steps 2 and 7. The controller calls `View::renderStandalone()` and the standalone template contains the complete HTML, CSS and forms. This is intended to eliminate any inherited layout/CSS condition that could hide the form or completion action.
