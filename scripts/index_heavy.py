from langchain_community.document_loaders import DirectoryLoader, PyPDFLoader
\
from langchain_text_splitters import RecursiveCharacterTextSplitter
\
from langchain_ollama import OllamaEmbeddings
\
from langchain_chroma import Chroma
\
import os

\
loader = DirectoryLoader("./docs", glob="**/*.pdf", loader_cls=PyPDFLoader)
\
docs = loader.load()
\
if not docs: print("❌ No PDFs found in ./docs"); exit()

\
text_splitter = RecursiveCharacterTextSplitter(chunk_size=1000, chunk_overlap=200)
\
splits = text_splitter.split_documents(docs)
\
embeddings = OllamaEmbeddings(model="nomic-embed-text")
\
vectorstore = Chroma.from_documents(
\
    documents=splits,
\
    embedding=embeddings,
\
    persist_directory="./chroma_db_heavy"
\
)
\
print("✅ Heavy RAG index created/updated in ./chroma_db_heavy")
