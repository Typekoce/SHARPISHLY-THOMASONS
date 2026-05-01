import mariadb
import json

class SearchService:
    @staticmethod
    def find_context(job_id, mock_query_vector):
        """
        In the future, this will use Cosine Similarity.
        For now, it pulls the most relevant chunks for the job.
        """
        # Connect to your MariaDB
        conn = mariadb.connect(user="root", password="any", host="127.0.0.1", database="app_db")
        cursor = conn.cursor()

        # MOCK SEARCH: Grab the first 3 chunks for this specific job
        query = "SELECT content FROM vectors WHERE job_id = ? LIMIT 3"
        cursor.execute(query, (job_id,))
        
        results = cursor.fetchall()
        conn.close()

        # Combine them into one block of 'Context'
        context_block = "\n---\n".join([row[0] for row in results])
        return context_block