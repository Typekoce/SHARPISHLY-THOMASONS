import os
from typing import Dict, Any, Optional

def render_template(template_name: str, context: Optional[Dict[str, Any]] = None) -> str:
    """
    Simple template renderer with {{ variable }} syntax.
    
    Args:
        template_name: Name of the template file (e.g., 'home.html')
        context: Dictionary of variables to inject into the template
    
    Returns:
        Rendered template as string, or error message if template not found.
    """
    if context is None:
        context = {}

    # Get the directory where this module lives (usually app/views/)
    base_dir = os.path.dirname(__file__)
    
    # Construct full path to the template
    template_path = os.path.join(base_dir, 'templates', template_name)

    if not os.path.exists(template_path):
        error_msg = f"Template not found: '{template_name}'\nLooked in: {template_path}"
        print(f"❌ {error_msg}")   # Helpful for debugging
        return f"<!-- {error_msg} -->"

    try:
        with open(template_path, 'r', encoding='utf-8') as f:
            content = f.read()

        # Replace {{ variable_name }} with context values
        for key, value in context.items():
            placeholder = f"{{{{ {key} }}}}"
            content = content.replace(placeholder, str(value))

        return content

    except Exception as e:
        error_msg = f"Error rendering template '{template_name}': {e}"
        print(f"❌ {error_msg}")
        return f"<!-- {error_msg} -->"