import os
from neo4j import GraphDatabase

class KnowledgeRetriever:
    def __init__(self):
        self.driver = GraphDatabase.driver(
            os.getenv("NEO4J_URI", "bolt://neo4j:7687"),
            auth=(os.getenv("NEO4J_USER", "neo4j"), os.getenv("NEO4J_PASSWORD"))
        )

    def get_latest_version(self, doc_id: str):
        """
        Follows the graph to find the chunk not superseded by any other.
        """
        query = """
        MATCH (c:Chunk {document_id: $doc_id})
        WHERE NOT (c)<-[:SUPERSEDES]-()
        RETURN c.content AS content, c.vector AS vector
        """
        with self.driver.session() as session:
            return session.run(query, doc_id=doc_id).data()

    def close(self):
        self.driver.close()