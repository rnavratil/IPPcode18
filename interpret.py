from sys import argv
from re import match


class FlagsClass:
    source_file = ""


flags = FlagsClass()  # Objekt obsahuje pouzite parametry skriptu.


def params(flag):
    arg_len = len(argv)  # Pocet parametru.
    use_source = False  # Osetruje duplicitu parametru.
    for x in range(1, arg_len):
        parameter = (argv[x])

        if match(r'^--help$', parameter):
            if arg_len == 2:
                print_help()
                exit(0)
            else:
                error_output(10)

        elif match(r'^--source=.*$', parameter):
            if not use_source:
                use_source = True
                flag.source_file = parameter[9:]
            else:
                error_output(10)

        else:
            error_output(10)


def print_help():
    help_text = """Projekt do predmetu IPP. Vytvoril Rostislav Navratil v roce 2018.\n
    Skript nacte XML reprezentaci programu ze zadaneho souboru a tento program s vyuzitim standardniho vstupu a vystupu
    interpretuje. Vstupni XML reprezentace je vytvorena ze zdrojoveho kodu v IPPcode18, napriklad skriptem parse.php.
    \n\nZakladni parametry bez rozsireni:\n
    --help  - vypise napovedu. Nelze ho kombinovat s jinymi parametry.\n
    --source=<file>  -  <file> = nazev XML reprezentace programu. Muze byt zadan absolutni, nebo relativni cestou.
    """
    print(help_text)


def error_output(number):
    if number == 10:
        exit(10)


def xml_process():
    print("TODO")


# Zpracovani parametru
params(flags)

exit(0)
