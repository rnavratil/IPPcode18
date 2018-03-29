from sys import argv
from re import match
from copy import deepcopy
import xml.etree.ElementTree as eT


class FlagClass:
    source_file = ""  # Cesta ke vstupnimu souboru.
    source = ""  # Obsah vstupniho souboru.


class InstructionsClass:
    opcode = ""

    def __init__(self):
        self.arguments = []  # Pole Argumentu instrukce


class ArgumentsClass:
    type = ""
    text = ""


flags = FlagClass()  # Objekt obsahuje pouzite parametry skriptu.


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
    """ Funkce na ukonceni skriptu s navratovou hodnotou jinou nez 0.
    :param number: cislo chyby.
    :return:
    """
    if number == 10:  # Chyba vstupnich parametru.
        exit(10)
    if number == 11:  # Chyba pri otevirani vstupnich souboru.
        exit(11)
    if number == 12:  # Chyba pri otevirani vystupnich souboru.
        exit(12)
    if number == 99:  # Interni chyba.
        exit(99)
    if number == 31:  # Chyba XML formatu.
        exit(31)
    if number == 32:  # Lexikalni chyba.
        exit(32)


def xml_process(flag):
    instructions_list = []  # List instrukci programu.
    try:
        tree = eT.parse(flag.source_file)  # Otevreni a zpracovani vstupniho XML souboru.
        root = tree.getroot()
    except Exception:  # TODO upresnit
        error_output(31)
    else:  # TODO zjistit co dela else a co raise
        if root.tag != "program":  # Kontrola nazvu korenoveho elementu.
            error_output(31)
        if root.attrib.get('language', 'None_key') == 'None_key':  # Kontrola nazvu atributu elementu.
            error_output(31)
        elif root.attrib.get('language') != "IPPcode18":
            error_output(31)

        root_len = len(root.attrib)  # Kontrola poctu atributu elementu.
        if 1 > root_len or root_len > 3:
            error_output(31)
        if root_len == 3:  # Korenovy element obsahuje 3 atributy.
            if root.attrib.get('name', 'None_key') == 'None_key':
                error_output(31)
            if root.attrib.get('description', 'None_key') == 'None_key':
                error_output(31)
        if root_len == 2:  # Korenovy element obsahuje 2 atributy.
            if root.attrib.get('name', 'None_key') == 'None_key':
                if root.attrib.get('description', 'None_key') == 'None_key':
                    error_output(31)

        # Zpracovani elementu 'instruction'.
        order_number = 1  # pro kontrolu poradi instrukce.
        for child in root:
            if child.tag != "instruction":  # Kontrola nazvu elementu instrukce.
                error_output(31)
            if len(child.attrib) != 2:  # Kontrola poctu atributu elementu.
                error_output(31)
            if child.attrib.get('order', 'None_key') == 'None_key':  # Kontrola nazvu atributu elementu.
                error_output(31)
            if child.attrib.get('opcode', 'None_key') == 'None_key':  # Kontrola nazvu atributu elementu.
                error_output(31)
            if child.attrib.get('order') != str(order_number):  # Kontrola poradi instrukce.
                error_output(31)
            instruction = deepcopy(InstructionsClass())  # Objekt pro instrukci. TODO oddelat deepcopy
            instruction.opcode = child.attrib.get('opcode')  # Zpracovani operacniho kodu instrukce.

            # Zpracovani  elementu 'arg'.
            arg_number = 1  # Pro kontrolu poradi argumentu
            for arg in child:
                argument = ArgumentsClass()  # Objekt argumentu.
                if arg.tag[3:] != str(arg_number) or arg.tag[:3] != "arg":  # Kontrola poradi a nazvu argumentu.
                    error_output(31)
                print(arg.attrib, arg.text)
                if len(arg.attrib) != 1 or arg.attrib.get('type', 'None_key') == 'None_key':  # Kontrola atributu.
                    error_output(31)

                argument.text = arg.text  # TODO posefit None
                argument.type = arg.attrib.get('type')
                instruction.arguments.append(argument)  # Pridani argumentu do objektu.
                arg_number += 1

            instructions_list.append(instruction)  # Ulozeni instrukce do pole instrukci.
            order_number += 1
        return instructions_list


# Zpracovani parametru.
params(flags)
# Zpracovani XML programu.
instructions = xml_process(flags)
print("Moskva")
exit(0)
