import ollama

def test_embedding():
    model = "jina/jina-embeddings-v2-small-en"
    text = "test query"
    
    print(f"--- Attempting to embed with model: {model} ---")
    try:
        # Check if the library has the 'embed' method (newer) or 'embeddings' (older)
        if hasattr(ollama, 'embed'):
            print("Detected ollama.embed() method.")
            response = ollama.embed(model=model, input=text)
        else:
            print("Detected ollama.embeddings() method.")
            response = ollama.embeddings(model=model, prompt=text)
            
        print("Raw Response Keys:", response.keys())
        
        # Check for embeddings
        if "embeddings" in response:
            embeddings = response["embeddings"]
            print(f"Embeddings field type: {type(embeddings)}")
            if isinstance(embeddings, list) and len(embeddings) > 0:
                print("Success! Embedding length:", len(embeddings[0]))
            else:
                print("Failure: 'embeddings' is present but empty or not a list.")
        else:
            print(f"Failure: No 'embeddings' key in response. Keys found: {list(response.keys())}")
            
    except Exception as e:
        print(f"CRITICAL ERROR: {e}")

if __name__ == "__main__":
    test_embedding()