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
    name = ""
    type = ""
    value = ""


class FrameClass:
    name = ""

    def __init__(self):
        self.variables = []  # Pole promennych ramce


class StackClass:
    def __init__(self):
        self.items = []

    def size(self):
        return len(self.items)

    def pop(self):
        return self.items.pop()

    def push(self, item):
        return self.items.append(item)

    def is_empty(self):
        return self.items == []

    def peek(self):
        return self.items[len(self.items)-1]


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
    if number == 53:  # Spatny typ promenne.
        exit(53)
    if number == 54:  # Pristup k neexistujici promenne.
        exit(54)
    if number == 55:  # Pristup k nedefinovanemu ramci.
        exit(55)
    if number == 56:  # Chybejici hodnota (v promenne, datovy zaboniku, zasobnik volani)
        exit(56)
    if number == 57:  # Deleni nulou.
        exit(57)
    if number == 59:  # Redefinice jiz existujici promenne.
        exit(59)


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
        is_variable(argument)
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


tmp_stack = StackClass()
frames_stack = StackClass()
global_frame = []
data_stack = StackClass()


def get_variable(variable):
    global global_frame
    global tmp_stack
    global frames_stack

    if variable.text[:2] == "LF":  # Hledana promenna je v Lokalnim ramci.
        if frames_stack.is_empty():  # Kontrola zda lokalni ramec neni prazdny.
            error_output(55)
        if not frames_stack.items[frames_stack.size() - 1].variables:  # Kontrola zda ramec neni prazdny.
            error_output(54)
        for variable_in_frame in frames_stack.items[frames_stack.size() - 1].variables:  # Hledani promenne.
            if variable_in_frame.name == variable.text[3:]:
                return variable_in_frame  # Hledana promenna je nalezena.
        error_output(54)  # Promenna se nenasla.

    elif variable.text[:2] == "TF":  # Cilova promenna je v temporary ramci.
        if tmp_stack.is_empty():  # Kontrola zda ramec neni prazdny.
            error_output(55)
        if not tmp_stack.items[tmp_stack.size() - 1].variables:  # Kontrola zda ramec neni prazdny.
            error_output(54)
        for variable_in_frame in tmp_stack.items[tmp_stack.size() - 1].variables:  # Hledani promenne.
            if variable_in_frame.name == variable.text[3:]:
                return variable_in_frame  # Hledana promenna je nalezena.
        error_output(54)  # Promenna se nenasla.

    elif variable.text[:2] == "GF":  # Cilova promenna je v globalnim ramci.
        if not global_frame:  # Kontrola prazdnosti ramce
            error_output(54)
        for variable_in_frame in global_frame:  # Hledani promenne.
            if variable_in_frame.name == variable.text[3:]:
                return variable_in_frame  # Hledana promenna je nalezena.
        error_output(54)  # Promenna se nenasla.


def var_put_in(target, symbol_par):
    global global_frame
    global tmp_stack
    global frames_stack
    global data_stack

    symbol = VariableClass()
    if symbol_par.type == "var":
        symbol = get_variable(symbol_par)
    else:
        symbol.value = symbol_par.value
        symbol.type = symbol_par.type

    if target.text[:2] == "LF":  # Cilova promenna je v Lokalnim ramci.
        if frames_stack.is_empty():  # Kontrola zda lokalni ramec neni prazdny.
            error_output(55)
        if not frames_stack.items[frames_stack.size() - 1].variables:  # Kontrola zda ramec neni prazdny.
            error_output(54)
        for variable_in_frame in frames_stack.items[frames_stack.size() - 1].variables:  # Hledani promenne.
            if variable_in_frame.name == target.text[3:]:
                variable_in_frame.value = symbol.value
                variable_in_frame.type = symbol.type
                return
        error_output(54)  # Promenna se nenasla.

    elif target.text[:2] == "TF":  # Cilova promenna je v temporary ramci.
        if tmp_stack.is_empty():  # Kontrola zda ramec neni prazdny.
            error_output(55)
        if not tmp_stack.items[tmp_stack.size() - 1].variables:  # Kontrola zda ramec neni prazdny.
            error_output(54)
        for variable_in_frame in tmp_stack.items[tmp_stack.size() - 1].variables:  # Hledani promenne.
            if variable_in_frame.name == target.text[3:]:
                variable_in_frame.value = symbol.value
                variable_in_frame.type = symbol.type
                return
        error_output(54)  # Promenna se nenasla.

    elif target.text[:2] == "GF":  # Cilova promenna je v globalnim ramci.
        if not global_frame:  # Kontrola prazdnosti ramce
            error_output(54)
        for variable_in_frame in global_frame:  # Hledani promenne.
            if variable_in_frame.name == target.text[3:]:
                variable_in_frame.value = symbol.value
                variable_in_frame.type = symbol.type
                return
        error_output(54)  # Promenna se nenasla.


def interpret(instructions_list):
    global global_frame
    global tmp_stack
    global frames_stack
    global data_stack

    x = 0
    while x < len(instructions_list):

        if instructions_list[x].opcode == "CREATEFRAME":
            frame = FrameClass()  # Vytvoreni prazdneho ramce.
            frame.name = "TF"  # Pojmenovani ramce.
            while not tmp_stack.is_empty():  # Ulozeni ramce na TMP zasobnik.
                tmp_stack.pop()
            tmp_stack.push(frame)

        elif instructions_list[x].opcode == "PUSHFRAME":
            if tmp_stack.is_empty():
                error_output(55)
            tmp_stack.items[0].name = "LF"
            frames_stack.push(tmp_stack.pop())

        elif instructions_list[x].opcode == "POPFRAME":
            if frames_stack.is_empty():
                error_output(55)
            frames_stack.items[0].name = "TF"
            tmp_stack.push(frames_stack.pop())

        elif instructions_list[x].opcode == "DEFVAR":
            variable = VariableClass()  # Vytvoreni objektu pro promennouo.
            variable.name = instructions_list[x].arguments[0].text[3:]  # Ulozeni jejiho jmena.
            tmp_frame_type = instructions_list[x].arguments[0].text[:2]  # Ulozeni jejiho typu.
            if tmp_frame_type == "TF":
                if tmp_stack.is_empty():
                    error_output(55)
                # if is_variable_in_frame(variable.name, tmp_stack, frames_stack, global_frame):
                    # error_output(59)
                tmp_stack.items[0].variables.append(variable)  # TODO co kdyz je to prazdny nazev
            elif tmp_frame_type == "LF":
                if frames_stack.is_empty():
                    error_output(55)
                # if is_variable_in_frame(variable.name, tmp_stack, frames_stack, global_frame):
                    # error_output(59)
                frames_stack.items[frames_stack.size() - 1].variables.append(variable)
            elif tmp_frame_type == "GF":
                # if is_variable_in_frame(variable.name, tmp_stack, frames_stack, global_frame):
                    # error_output(59)
                global_frame.append(variable)

        elif instructions_list[x].opcode == "PUSHS":
            if instructions_list[x].arguments[0].type == "var":
                variable_to_symb = get_variable(instructions_list[x].arguments[0])
                variable_to_symb.name = ""
                if not variable_to_symb.value:  # Promenna neobsahuje hodnotu.
                    error_output(56)
                data_stack.push(variable_to_symb)  # Vlozeni promenne na zasobnik.
            else:
                symbol = VariableClass()  # Vytvoreni objektu pro vlozeni na datovy zasobnik.
                symbol.value = instructions_list[x].arguments[0].text
                symbol.type = instructions_list[x].arguments[0].type
                data_stack.push(symbol)  # Vlozeni symbolu na zasobnik.

        elif instructions_list[x].opcode == "POPS":
            if data_stack.is_empty():
                error_output(56)
            var_put_in(instructions_list[x].arguments[0], data_stack.pop())

        elif instructions_list[x].opcode == "MOVE":
            variable = instructions_list[x].arguments[0]
            symbol = VariableClass()
            symbol.type = instructions_list[x].arguments[1].type
            symbol.value = instructions_list[x].arguments[1].text
            var_put_in(variable, symbol)  # Priradi symbol promenne.

        elif instructions_list[x].opcode == "ADD":
            result = 0
            for y in range(1, 3):
                symbol = instructions_list[x].arguments[y]
                if symbol.type == "var":
                    variable_tmp = get_variable(symbol)
                    if not variable_tmp.value:
                        error_output(56)
                    if not variable_tmp.type == "int":
                        error_output(53)
                    result += int(variable_tmp.value)
                else:
                    if not symbol.type == "int":
                        error_output(53)
                    result += int(symbol.text)
            result_var = VariableClass()
            result_var.type = "int"
            result_var.value = str(result)
            var_put_in(instructions_list[x].arguments[0], result_var)

        elif instructions_list[x].opcode == "SUB":
            result = 0
            for y in range(1, 3):
                symbol = instructions_list[x].arguments[y]
                if symbol.type == "var":
                    variable_tmp = get_variable(symbol)
                    if not variable_tmp.value:
                        error_output(56)
                    if not variable_tmp.type == "int":
                        error_output(53)
                    if y == 1:
                        result += int(variable_tmp.value)
                    else:
                        result -= int(variable_tmp.value)
                else:
                    if not symbol.type == "int":
                        error_output(53)
                    if y == 1:
                        result += int(symbol.text)
                    else:
                        result -= int(symbol.text)
            result_var = VariableClass()
            result_var.type = "int"
            result_var.value = str(result)
            var_put_in(instructions_list[x].arguments[0], result_var)

        elif instructions_list[x].opcode == "MUL":
            result = 0
            for y in range(1, 3):
                symbol = instructions_list[x].arguments[y]
                if symbol.type == "var":
                    variable_tmp = get_variable(symbol)
                    if not variable_tmp.value:
                        error_output(56)
                    if not variable_tmp.type == "int":
                        error_output(53)
                    if y == 1:
                        result += int(variable_tmp.value)
                    else:
                        result *= int(variable_tmp.value)
                else:
                    if not symbol.type == "int":
                        error_output(53)
                    if y == 1:
                        result += int(symbol.text)
                    else:
                        result *= int(symbol.text)
            result_var = VariableClass()
            result_var.type = "int"
            result_var.value = str(result)
            var_put_in(instructions_list[x].arguments[0], result_var)

        elif instructions_list[x].opcode == "IDIV":
            result = 0
            for y in range(1, 3):
                symbol = instructions_list[x].arguments[y]
                if symbol.type == "var":
                    variable_tmp = get_variable(symbol)
                    if not variable_tmp.value:
                        error_output(56)
                    if not variable_tmp.type == "int":
                        error_output(53)
                    if y == 1:
                        result += int(variable_tmp.value)
                    else:
                        if str(variable_tmp.value) == "0":
                            error_output(57)
                        result = int(result / int(variable_tmp.value))
                else:
                    if not symbol.type == "int":
                        error_output(53)
                    if y == 1:
                        result += int(symbol.text)
                    else:
                        if symbol.text == "0":
                            error_output(57)
                        result = int(result / int(symbol.text))
            result_var = VariableClass()
            result_var.type = "int"
            result_var.value = str(result)
            var_put_in(instructions_list[x].arguments[0], result_var)

        x += 1
    print("Petrohrad")


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
