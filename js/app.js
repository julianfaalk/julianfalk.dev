/**
 * Main application JavaScript
 * Handles UI interactions for the site
 */
$(function() {
    // Social media handle input toggle
    initSocialMediaInput();

    // Guestbook form toggle
    initGuestbookFormToggle();
});

/**
 * Initialize social media platform/handle input interaction
 * Enables/disables handle input based on platform selection
 */
function initSocialMediaInput() {
    var $platformSelect = $('#social_media_platform');
    var $handleInput = $('#social_media_handle');

    if (!$platformSelect.length || !$handleInput.length) return;

    function updateHandleInput() {
        if ($platformSelect.val()) {
            $handleInput.prop('disabled', false).attr('placeholder', '@username');
        } else {
            $handleInput.prop('disabled', true).val('').attr('placeholder', '@username');
        }
    }

    $platformSelect.on('change', updateHandleInput);
    updateHandleInput();
}

/**
 * Initialize guestbook form toggle button
 * Shows/hides the guestbook entry form
 */
function initGuestbookFormToggle() {
    var $toggleBtn = $('#toggleGuestbookForm');
    var $formWrapper = $('#guestbookFormWrapper');

    if (!$toggleBtn.length || !$formWrapper.length) return;

    var isFormOpen = false;

    $toggleBtn.on('click', function() {
        isFormOpen = !isFormOpen;
        $formWrapper.slideToggle(300);
        $toggleBtn.text(isFormOpen ? '− Close' : '+ Create Entry');
    });
}

/**
 * Trigger welcome celebration effect
 * Called when user confirms newsletter subscription
 */
function triggerWelcomeCelebration() {
    var container = document.getElementById('newsletter');
    if (container && typeof Confetti !== 'undefined') {
        Confetti.launch(container, { particleCount: 50 });
    }
}
