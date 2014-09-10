# -*- coding: utf-8 -*-
import sys, os
sys.path.insert(0, '../')
#needs_sphinx = '1.0'

extensions = ['sphinx.ext.autodoc', 'sphinx.ext.viewcode']

templates_path = ['_templates']

source_suffix = '.rst'
master_doc = 'index'

# General information about the project.
project = u'FoolFuuka'
copyright = u'2012-2014, Foolz'


version = '2.0.x'
release = '2.0.x'

exclude_patterns = ['_build']
add_function_parentheses = True
add_module_names = True
show_authors = False
pygments_style = 'sphinx'
modindex_common_prefix = ['foolfuuka']
html_theme = 'nature'
html_static_path = ['_static']
htmlhelp_basename = 'foolfuukadoc'
