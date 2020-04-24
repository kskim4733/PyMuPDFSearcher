# PyMuPDFSearcher
PDF term and context searcher using PyMuPDF Library. Extract page size, page number, and term coordinate

# PyMuSearcher.py
PyMuSearcher.py returns coordinate of the queried term and coordinates of it's corresponding text.  
The code was designed to work with [Bookreader Plugin for Resourcespace](https://github.com/leslie-lau/bookreader) and uses the following [API](https://openlibrary.org/dev/docs/api/search_inside).  
PyMuSearcher.py will get called by [search_inside.php](https://github.com/leslie-lau/bookreader/blob/master/search_inside.php).  
This project was based on [this](https://github.com/leslie-lau/fulltextsearch/tree/master/src/fulltextsearch) project.  

## Requirements
This project requires [python 3.7]((https://linuxize.com/post/how-to-install-python-3-7-on-ubuntu-18-04/)) and pdfminer to work.  
Install pip for python 3.7 (python3.7 -m pip install pip).  
Make sure that pdfminer library is accessible from the python that PHP is using.  
You can installation step of PyMuPDF on Ubuntu in https://github.com/pymupdf/PyMuPDF/wiki/Ubuntu-Installation-Experienc


Please replace existing search_inside.php with the [new one](https://github.com/kskim4733/PyMuPDFSearcher/blob/master/search_inside.php) from this repository.  


## Overview
`PyMuSearcher.py` finds term and text coordinate in given PDF File.  


## Usage
`PyMuSearcher.py` takes 6 arguments.  

`<item-id> <file-path> <query-term> <callback> <css-or-abbyy>`

Ex) `1 C:/Users/user1/Desktop/test.pdf kyle 1 abbyy` 

