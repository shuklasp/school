import sys, json, importlib, os

def main():
    try:
        module_name = sys.argv[1]
        func_name = sys.argv[2]
        
        # Add bridge directory to path
        bridge_dir = os.path.dirname(os.path.abspath(__file__))
        if bridge_dir not in sys.path:
            sys.path.insert(0, bridge_dir)

        args_raw = sys.stdin.read()
        args = json.loads(args_raw) if args_raw else []

        module = importlib.import_module(module_name)
        func = getattr(module, func_name)
        
        if isinstance(args, list):
            result = func(*args)
        elif isinstance(args, dict):
            result = func(**args)
        else:
            result = func()

        print(json.dumps(result))
    except Exception as e:
        sys.stderr.write(str(e))
        sys.exit(1)

if __name__ == "__main__":
    main()