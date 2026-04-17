import os

class WorkerModel:
    def __init__(self):
        self.upload_dir = "storage/uploads"
        self.results_dir = "storage/results"

    def execute_np_logic(self):
        """
        The Core 'Next Task':
        1. Find the cleaned file
        2. Perform Feature Extraction
        3. Save transformed data
        """
        try:
            # Lateral Thinking: Find the most recent file if no ID is passed
            files = [f for f in os.listdir(self.upload_dir) if os.path.isfile(os.path.join(self.upload_dir, f))]
            if not files:
                return False

            target_file = os.path.join(self.upload_dir, files[-1])
            
            # --- START NP TASK ---
            # Imagine we are extracting specific neural patterns here
            with open(target_file, 'r') as f:
                raw_data = f.read()
            
            # Perform a 'Neural' transformation (simulated pattern extraction)
            processed_data = f"NP_TRANSFORMED_DATA: {raw_data[::-1]}" # Example: reverse data
            
            # Save the result
            if not os.path.exists(self.results_dir):
                os.makedirs(self.results_dir)
                
            result_path = os.path.join(self.results_dir, f"processed_{files[-1]}")
            with open(result_path, 'w') as f:
                f.write(processed_data)
            # --- END NP TASK ---

            return True
        except Exception as e:
            print(f"Model Error: {e}")
            return False