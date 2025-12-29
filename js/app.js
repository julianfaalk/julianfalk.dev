/**
 * Main application JavaScript
 * Handles UI interactions for the site
 */
$(function() {
    // Social media handle input toggle
    initSocialMediaInput();

    // Guestbook form toggle
    initGuestbookFormToggle();

    // Show more entries toggle
    initShowMoreEntries();

    // Message truncation
    initMessageTruncation();
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

/**
 * Initialize show more entries toggle
 * Expands/collapses hidden guestbook entries
 */
function initShowMoreEntries() {
    var $showMoreBtn = $('#showMoreEntries');
    var $entriesList = $('.entries-list');

    if (!$showMoreBtn.length || !$entriesList.length) return;

    var isExpanded = false;
    var originalText = $showMoreBtn.find('.show-more-text').text();

    $showMoreBtn.on('click', function() {
        isExpanded = !isExpanded;
        $entriesList.toggleClass('expanded', isExpanded);
        $showMoreBtn.toggleClass('expanded', isExpanded);
        $showMoreBtn.find('.show-more-text').text(isExpanded ? 'Show less' : originalText);
    });
}

/**
 * Initialize message truncation for long guestbook entries
 * Expands truncated messages when "Read more" is clicked
 */
function initMessageTruncation() {
    $('.entry-message.truncated').each(function() {
        var $message = $(this);
        var $textSpan = $message.find('.message-text');
        var $expandBtn = $message.find('.entry-expand-btn');
        var fullMessage = $message.data('full-message');
        var truncatedHtml = $textSpan.html();

        $expandBtn.on('click', function() {
            var isExpanded = $message.hasClass('expanded');
            if (isExpanded) {
                // Collapse back
                $textSpan.html(truncatedHtml);
                $expandBtn.text('Read more');
                $message.removeClass('expanded');
            } else {
                // Expand to full
                $textSpan.html(fullMessage.replace(/\n/g, '<br>'));
                $expandBtn.text('Show less');
                $message.addClass('expanded');
            }
        });
    });
}
