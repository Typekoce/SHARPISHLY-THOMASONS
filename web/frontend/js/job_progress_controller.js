const JobProgressController = {
    monitor: function(jobId, onComplete, onError) {
        const interval = setInterval(async () => {
            try {
                const response = await fetch(`/php/job/status/${jobId}`);
                const data = await response.json();
                
                // 1. Update the UI directly
                const pipelineDiv = document.getElementById('neural-pipeline');
                if (pipelineDiv) {
                    pipelineDiv.innerHTML = `
                        <div class="alert alert-info">
                            Job #${jobId} status: <strong>${data.state}</strong>
                        </div>
                    `;
                }

                // 2. Lifecycle management
                if (data.state === 'completed') {
                    clearInterval(interval);
                    onComplete(data);
                } else if (data.state === 'failed') {
                    clearInterval(interval);
                    onError(data);
                }
            } catch (error) {
                console.error("Polling error:", error);
                clearInterval(interval);
            }
        }, 2000);
    }
};