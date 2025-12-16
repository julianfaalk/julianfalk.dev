/**
 * Confetti celebration effect
 * Triggers colorful confetti particles from a target element
 */
var Confetti = (function() {
    var colors = ['#4da3ff', '#ff6b6b', '#4ecdc4', '#ffe66d', '#ff8b94', '#a8e6cf'];

    function launch(targetElement, options) {
        options = options || {};
        var particleCount = options.particleCount || 50;
        var scrollToTarget = options.scrollToTarget !== false;

        if (!targetElement) return;

        var rect = targetElement.getBoundingClientRect();

        for (var i = 0; i < particleCount; i++) {
            createParticle(rect);
        }

        if (scrollToTarget) {
            setTimeout(function() {
                targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
        }
    }

    function createParticle(rect) {
        var confetti = document.createElement('div');
        confetti.className = 'confetti-particle';
        confetti.style.cssText =
            'position:fixed;' +
            'width:' + (Math.random() * 10 + 5) + 'px;' +
            'height:' + (Math.random() * 10 + 5) + 'px;' +
            'background:' + colors[Math.floor(Math.random() * colors.length)] + ';' +
            'left:' + (rect.left + rect.width / 2) + 'px;' +
            'top:' + (rect.top + window.scrollY) + 'px;' +
            'border-radius:' + (Math.random() > 0.5 ? '50%' : '2px') + ';' +
            'pointer-events:none;' +
            'z-index:9999;' +
            'opacity:1;';
        document.body.appendChild(confetti);

        var angle = (Math.random() * 120 + 210) * Math.PI / 180;
        var velocity = Math.random() * 300 + 200;
        var vx = Math.cos(angle) * velocity;
        var vy = Math.sin(angle) * velocity;
        var rotation = Math.random() * 720 - 360;

        animateParticle(confetti, vx, vy, rotation);
    }

    function animateParticle(el, vx, vy, rot) {
        var startTime = null;
        var startX = parseFloat(el.style.left);
        var startY = parseFloat(el.style.top);
        var gravity = 400;

        function animate(timestamp) {
            if (!startTime) startTime = timestamp;
            var elapsed = (timestamp - startTime) / 1000;

            var x = startX + vx * elapsed;
            var y = startY + vy * elapsed + 0.5 * gravity * elapsed * elapsed;
            var opacity = Math.max(0, 1 - elapsed / 2);

            el.style.left = x + 'px';
            el.style.top = y + 'px';
            el.style.opacity = opacity;
            el.style.transform = 'rotate(' + (rot * elapsed) + 'deg)';

            if (elapsed < 2) {
                requestAnimationFrame(animate);
            } else {
                el.remove();
            }
        }
        requestAnimationFrame(animate);
    }

    return {
        launch: launch
    };
})();
