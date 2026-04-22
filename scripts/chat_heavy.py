import sys
\
from langchain_ollama import ChatOllama, OllamaEmbeddings
\
from langchain_chroma import Chroma
\
from langchain_core.prompts import ChatPromptTemplate
\
from langchain_core.runnables import RunnablePassthrough
\
from langchain_core.output_parsers import StrOutputParser

\
embeddings = OllamaEmbeddings(model="nomic-embed-text")
\
vectorstore = Chroma(persist_directory="./chroma_db_heavy", embedding_function=embeddings)
\
retriever = vectorstore.as_retriever(search_kwargs={"k": 4})
\
llm = ChatOllama(model="llama3.1", temperature=0.3)
\
template = """Answer the question based only on the following context:\n{context}\nQuestion: {question}"""
\
prompt = ChatPromptTemplate.from_template(template)
\
chain = ({"context": retriever, "question": RunnablePassthrough()} | prompt | llm | StrOutputParser())

\
print("RAG Chat ready! (type exit to quit)\n")
\
while True:
\
    try:
\
        q = input("You: ")
\
        if q.lower() in ["exit", "quit"]: break
\
        print("Assistant:", chain.invoke(q))
\
        print("-" * 60)
\
    except KeyboardInterrupt: break
