const SyncApplyController = {
    async create() {
        const jobId = document.getElementById('syncapply-job-select')?.value || '01_webdesigner';
        
        try {
            const data = await App.api('jobapply/prepare', { job_id: jobId });
            const container = document.getElementById('job-apply-output');
            if (container) {
                container.innerHTML = `<strong style="color: #00ff80; display: block; margin-bottom: 0.25rem;">Application Pipeline Triggered</strong>Processed vacancy ID: ${jobId}`;
            }
        } catch (e) {
            const container = document.getElementById('job-apply-output');
            if (container) {
                container.innerHTML = `<strong style="color: #ff0055; display: block; margin-bottom: 0.25rem;">Sync Error</strong>${e.message}`;
            }
        }
    },
    async read() {},
    async update() {},
    async delete() {},
};