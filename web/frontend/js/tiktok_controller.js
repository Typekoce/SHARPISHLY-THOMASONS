/**
 * TikTok Controller: UX Enhanced
 */
const TiktokController = {
    async bindPosts() {
        // App.loadView (if needed) - assumes HTML is already injected
        const btn = document.getElementById('tiktok-submit');
        const contentInput = document.getElementById('tiktok-content');

        if (!btn || !contentInput) return;

        btn.onclick = (e) => {
            // Visual feedback: brief pulse
            btn.style.opacity = '0.5';
            setTimeout(() => btn.style.opacity = '1', 200);

            AgentController.tiktokPost(contentInput.value);
        };
    }
};