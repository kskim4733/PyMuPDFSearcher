import sys
try:
    import fitz
except ImportError:
    sys.exit("""You need Pymupdf! try visiting https://github.com/pymupdf/PyMuPDF/wiki/Ubuntu-Installation-Experience """)

class FoundTerm:
    def __init__(self, term, term_coord, page_num, page_size, context):
        self.term = term
        self.term_coord = term_coord
        self.page_num = page_num
        self.page_size = page_size
        self.context = context

    def get_term_bound(self):
        # get coordinate of the term(actual word that the user searched)
        return self.term_coord[2], self.term_coord[3], self.term_coord[1], self.term_coord[0]

    def get_page_num(self):
        # return page number of where the term was found
        return self.page_num

    def get_page_size(self):
        #return the size of the page that the term was found
        return self.page_size[0], self.page_size[1]

    def get_text_bound(self):
        # seems like text_bound coordinates are not being used anywhere
        return 0, 0, 0, 0

    def getContext(self):
        return self.context.replace('\n', '')

    def print_info(self):
        print("text:%s" % (self.getContext()))
        print("page_num:%d" % (self.get_page_num()))
        print("page_size:%.5f,%.5f" % (self.get_page_size()))
        print("text_bounds:%.5f,%.5f,%.5f,%.5f" % (self.get_text_bound()))
        print("term_bounds:%.5f,%.5f,%.5f,%.5f\n" % (self.get_term_bound()))


class PDFTermSearch:
    def __init__(self, cb, ia, query_term, file_path):
        self.cb = cb
        self.ia = ia
        self.query_term = query_term.lower()
        self.file_path = file_path
        self.found_terms = []
        try:
            self.doc = fitz.open(self.file_path)  # Add error check here
        except:
            print("Could not open file ", self.file_path)
            exit(1)

        self.total_page_num = len(self.doc)

    def findContext(self, og_page_content, page_content, query_term, query_index):
        current_char = page_content[query_index]

        if query_index == 0:
            start_index = 0
        else:
            start_index = query_index
            while current_char != "\n":
                start_index -= 1
                current_char = page_content[start_index]

        if query_index > len(page_content) - len(query_term):
            finish_index = len(page_content) - 1
        else:
            finish_index = query_index + len(query_term)
            current_char = page_content[finish_index]
            while current_char != "\n":
                finish_index += 1
                current_char = page_content[finish_index]

        return (og_page_content[start_index:query_index] +
                "{{{" +  og_page_content[query_index:query_index + len(query_term)] + "}}}" +
                og_page_content[query_index + len(query_term):finish_index])

    def printResult(self):
        print("callback:%s" % str(self.cb))
        print("ia:%s" % str(self.ia))
        print("term:%s" % self.query_term)
        print("pages:%d\n" % self.total_page_num)
        for term in self.found_terms:
            term.print_info()

    def getSearchResult(self):
        for page in self.doc:
            text_instances = page.searchFor(self.query_term, hit_max=999999) # otherwise default search max is 16
            if text_instances != []:
                og_page_content = str(page.getText())
                page_content = str(page.getText()).lower()
                index_in_context = [i for i in range(len(page_content)) if page_content.startswith(self.query_term, i)] # this contains all the indexes the query term occured in the page content
                # print("Page number", page.number, "Page size ", round(page.MediaBoxSize[0],5), round(page.MediaBoxSize[1],5))

                for i in range(0, len(text_instances)):
                    if (len(index_in_context) == len(text_instances)): # If everything is 1 to 1 matching between text_instances and index_in_context. It should always be this case
                        context = self.findContext(og_page_content, page_content, self.query_term, index_in_context[i])
                        term_coord = text_instances[i]
                    else: #this is for case when sometime there is more text_instance than the index_in_content, this may happen due to having more than one text layer of the same text.
                        context = "{{{" + self.query_term + "}}}"
                        term_coord = text_instances[i]
                    self.found_terms.append(FoundTerm(term=self.query_term,
                                                      term_coord=term_coord,
                                                      page_num=page.number,
                                                      page_size=(round(page.MediaBoxSize[0], 5), round(page.MediaBoxSize[1], 5)),
                                                      context=context))
        self.printResult()


if __name__ == "__main__":
    if len(sys.argv) != 6:
        print("ERROR: Invalid Arguments Length")
        print("Usage: <item-id> <file-path> <query-term> <callback> <css-or-abbyy>")
        exit(1)

    item_id = sys.argv[1]

    path = sys.argv[2]
    query_term = sys.argv[3]
    callback = sys.argv[4]
    pdf_style = sys.argv[5]
    pdf_searcher = PDFTermSearch(callback, item_id, query_term, path)
    pdf_searcher.getSearchResult()
