from app.routes import route_request

def main():
    print("=== PyMVC Simple Runner ===")
    print("Enter path (e.g. /, /users) or 'quit' to exit.\n")
    
    while True:
        path = input("Path > ").strip()
        if path.lower() in ['quit', 'exit', 'q']:
            print("Goodbye!")
            break
        if not path.startswith('/'):
            path = '/' + path
        response = route_request(path)
        print("\n" + "="*60)
        print(response)
        print("="*60 + "\n")

if __name__ == "__main__":
    main()
