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


class VariableClass:
    frame = ""
    type = ""
    value = ""


def params(flag):
    """ Funkce pro zpracovani vstupnich argumentu skriptu.
    :param flag: objekt obsahujici informace o zdrojovem souboru.
    :return:
    """
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
    """ Funkce pro vypis napovedy.
    :return:
    """
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
    """ Funkce pro zpracovani vstupniho souboru skriptu (XML vystup).
    :param flag: objekt obsahujici informace o zdrojovem souboru.
    :return: instructions_list : pole instrukci.
    """
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
                if len(arg.attrib) != 1 or arg.attrib.get('type', 'None_key') == 'None_key':  # Kontrola atributu.
                    error_output(31)

                argument.text = arg.text  # TODO posefit None
                argument.type = arg.attrib.get('type')
                instruction.arguments.append(argument)  # Pridani argumentu do objektu.
                arg_number += 1

            instructions_list.append(instruction)  # Ulozeni instrukce do pole instrukci.
            order_number += 1
        return instructions_list


def lexical_analysis(instructions_list):
    """ Funkce provede lexikalni analyzu instrukci.
    :param instructions_list: pole obsahujici instrukce.
    :return:
    """
    var_symb = ["MOVE", "NOT", "INT2CHAR", "TYPE", "STRLEN"]
    nothing = ["CREATEFRAME", "PUSHFRAME", "POPFRAME", "RETURN", "BREAK"]
    var_type = ["READ"]
    label_symb_symb = ["JUMPIFEQ", "JUMPIFNEQ"]
    var = ["DEFVAR", "POPS"]
    label = ["CALL", "JUMP", "LABEL"]
    symb = ["PUSHS", "WRITE", "DPRINT"]
    var_symb_symb = ["ADD", "SUB", "MUL", "IDIV", "LT", "GT", "EQ", "AND", "OR", "STRI2INT", "CONCAT", "SETCHAR",
                     "GETCHAR"]

    for instruction in instructions_list:
        if instruction.opcode in var_symb:
            if len(instruction.arguments) != 2:
                error_output(32)
            is_variable(instruction.arguments[0])
            is_symbol(instruction.arguments[1])

        elif instruction.opcode in nothing:
            if len(instruction.arguments) != 0:
                error_output(32)

        elif instruction.opcode in var_type:
            if len(instruction.arguments) != 2:
                error_output(32)
            is_variable(instruction.arguments[0])
            is_type(instruction.arguments[1])

        elif instruction.opcode in label_symb_symb:
            if len(instruction.arguments) != 3:
                error_output(32)
            is_label(instruction.arguments[0])
            is_symbol(instruction.arguments[1])
            is_symbol(instruction.arguments[2])

        elif instruction.opcode in var:
            if len(instruction.arguments) != 1:
                error_output(32)
            is_variable(instruction.arguments[0])

        elif instruction.opcode in label:
            if len(instruction.arguments) != 1:
                error_output(32)
            is_label(instruction.arguments[0])

        elif instruction.opcode in symb:
            if len(instruction.arguments) != 1:
                error_output(32)
            is_symbol(instruction.arguments[0])

        elif instruction.opcode in var_symb_symb:
            if len(instruction.arguments) != 3:
                error_output(32)
            is_variable(instruction.arguments[0])
            is_symbol(instruction.arguments[1])
            is_symbol(instruction.arguments[2])
        else:
            error_output(32)


def is_variable(argument):
    """ Funkce overi zda format argumentu instrukce odpovida promenne.
    :param argument: argument instrukce.
    :return:
    """
    if not argument.text:  # Kontrol zda obsahuje hodnotu.
        error_output(32)
    if not match(r'^(LF|GF|TF)@([a-zA-Z\_$\-\&\%\*]+)$', argument.text) or argument.type != "var":  # TODO nebere cisla
        error_output(32)


def is_label(argument):
    """ Funkce overi zda format argumentu instrukce odpovida navesti.
    :param argument: argument instrukce.
    :return:
    """
    if not argument.text:  # Kontrol zda obsahuje hodnotu.
        error_output(32)
    if not match(r'^([a-zA-Z\_$\-\&\%\*]+)$', argument.text) or argument.type != "label":  # TODO ma to brat cisla?
        error_output(32)


def is_type(argument):
    """ Funkce overi zda format argumentu instrukce odpovida typu.
    :param argument: argument instrukce.
    :return:
    """
    if not argument.text:  # Kontrol zda obsahuje hodnotu.
        error_output(32)
    type_list = ["string", "bool", "int"]
    if argument.text not in type_list or argument.type != "type":
        error_output(32)


def is_symbol(argument):
    """ Funkce kontroluje format symbolu.
    :param argument: argument instrukce.
    :return:
    """
    if argument.type == "string":
        if not is_string(argument):
            error_output(32)
    elif argument.type == "bool":
        if not is_bool(argument):
            error_output(32)
    elif argument.type == "int":
        if not is_int(argument):
            error_output(32)
    elif argument.type == "var":
        if not is_variable(argument):
            error_output(32)
    else:
        error_output(32)


def is_bool(argument):
    """ Funkce pro kontrolu formatu hodnot typu bool.
    :param argument:  argument instrukce.
    :return: 1 - spravny format , 0 - chybny format.
    """
    if not argument.text:  # Kontrol zda obsahuje hodnotu.
        error_output(32)
    if match(r'^(true|false)$', argument.text):
        return 1
    else:
        return 0


def is_int(argument):
    """ Funkce pro kontrolu formatu hodnot typu integer.
    :param argument:  argument instrukce.
    :return: 1 - spravny format , 0 - chybny format.
    """
    if not argument.text:  # Kontrol zda obsahuje hodnotu.
        error_output(32)
    try:
        int(argument.text)
        return 1
    except ValueError:
        return 0


def is_string(argument):
    """ Funkce pro kontrolu formatu hodnot typu string.
    :param argument:  argument instrukce.
    :return: 1 - spravny format , 0 - chybny format.
    """
    if not argument.text:  # Kontrol zda obsahuje hodnotu.
        return 1
    x = 0
    while x < len(argument.text):
        char = argument.text[x]
        if char == "\\":  # Kontrola escape sekvenci.
            if int(len(argument.text) < int(x) + 3):
                return 0
            escape = argument.text[x+1:]
            if not match(r'^[0-9][0-9][0-9]$', escape[:3]):
                return 0
            x += 3
        if char == "#":  # Nepovolen znak.
            return 0
        x += 1
    return 1


def interpret(instructions_list):
    print("TODO")


# Zpracovani parametru.
params(flags)
# Zpracovani XML programu.
instructions = xml_process(flags)
# Lexikalni analyza.
lexical_analysis(instructions)
# Interpretace instrukci.
interpret(instructions)
print("Moskva")
exit(0)
