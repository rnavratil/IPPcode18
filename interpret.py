from sys import argv
from sys import stderr
from re import match
from re import IGNORECASE
import codecs
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


class LabelClass:
    name = ""
    number = ""


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
    if number == 52:  # Semanticka chyba.
        exit(52)
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
    if number == 58:  # Chybna prace s retezcem.
        exit(58)
    if number == 59:  # Redefinice jiz existujici promenne.
        exit(59)


def xml_process(flag):
    """ Funkce pro zpracovani vstupniho souboru skriptu (XML vystup).
    :param flag: objekt obsahujici informace o zdrojovem souboru.
    :return: instructions_list : pole instrukci.
    """
    instructions_list = []  # List instrukci programu.
    try:
        with codecs.open(flag.source_file, "r", "utf-8") as xml_file_utf8:
            tree = eT.parse(xml_file_utf8)  # Otevreni a zpracovani vstupniho XML souboru.
            root = tree.getroot()
    except Exception:  # TODO upresnit
        error_output(31)
    else:
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
            instruction = InstructionsClass()  # Objekt pro instrukci.
            instruction.opcode = child.attrib.get('opcode')  # Zpracovani operacniho kodu instrukce.

            # Zpracovani  elementu 'arg'.
            arg_list = []  # Nacteni vsech argumentu do pole.
            for arg in child:
                arg_list.append(arg)

            arg_number = 1  # aktualni poradi argumentu pro zpracovani.
            while arg_list:  # Pokud mame nezpracovane argumenty.
                    argument = ArgumentsClass()  # Objekt argumentu.
                    for index in range(0,  len(arg_list)):
                        arg = arg_list[index]  # Prohledavani pole argumentu.
                        if arg.tag[3:] == str(arg_number):  # Cislo hledaneho argumentu sedi.
                            if not arg.tag[:3] == "arg":  # Kontrola nazvu argumentu.
                                error_output(31)
                            if len(arg.attrib) != 1 or arg.attrib.get('type', 'None_key') == 'None_key':
                                error_output(31)
                            argument.text = arg.text
                            argument.type = arg.attrib.get('type')
                            instruction.arguments.append(argument)  # Pridani argumentu do objektu.
                            arg_number += 1
                            arg_list.pop(index)
                            break
                        if index == len(arg_list) - 1:  # Argument chybi.
                            error_output(31)

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
                error_output(31)
            one = instruction.arguments[0].type
            two = instruction.arguments[1].type
            if not one == "var":
                error_output(31)
            if not two == "string" and two == "bool" and two == "int" and two == "var":
                error_output(31)
            is_variable(instruction.arguments[0])
            is_symbol(instruction.arguments[1])

        elif instruction.opcode in nothing:
            if len(instruction.arguments) != 0:
                error_output(31)

        elif instruction.opcode in var_type:
            if len(instruction.arguments) != 2:
                error_output(31)
            one = instruction.arguments[0].type
            two = instruction.arguments[1].type
            if not one == "var":
                error_output(31)
            if two != "type":
                error_output(31)
            is_variable(instruction.arguments[0])
            is_type(instruction.arguments[1])

        elif instruction.opcode in label_symb_symb:
            if len(instruction.arguments) != 3:
                error_output(31)
            one = instruction.arguments[0].type
            two = instruction.arguments[1].type
            three = instruction.arguments[2].type
            if not one == "label":
                error_output(31)
            if not two == "string" and two == "bool" and two == "int" and two == "var":
                error_output(31)
            if not three == "string" and three == "bool" and three == "int" and three == "var":
                error_output(31)
            is_label(instruction.arguments[0])
            is_symbol(instruction.arguments[1])
            is_symbol(instruction.arguments[2])

        elif instruction.opcode in var:
            if len(instruction.arguments) != 1:
                error_output(31)
            one = instruction.arguments[0].type
            if not one == "var":
                error_output(31)
            is_variable(instruction.arguments[0])

        elif instruction.opcode in label:
            if len(instruction.arguments) != 1:
                error_output(31)
            one = instruction.arguments[0].type
            if not one == "label":
                error_output(31)
            is_label(instruction.arguments[0])

        elif instruction.opcode in symb:
            if len(instruction.arguments) != 1:
                error_output(31)
            one = instruction.arguments[0].type
            if not one == "string" and one == "bool" and one == "int" and one == "var":
                error_output(31)
            is_symbol(instruction.arguments[0])

        elif instruction.opcode in var_symb_symb:
            if len(instruction.arguments) != 3:
                error_output(31)
            one = instruction.arguments[0].type
            two = instruction.arguments[1].type
            three = instruction.arguments[2].type
            if not one == "var":
                error_output(31)
            if not two == "string" and two == "bool" and two == "int" and two == "var":
                error_output(31)
            if not three == "string" and three == "bool" and three == "int" and three == "var":
                error_output(31)
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
    if not match(r'^(LF|GF|TF)@([a-zA-Z\_\$\-\&\%\*][a-zA-Z\_\$\-\&\%\*\d]*)$', argument.text):
        error_output(32)
    if not argument.type == "var":
        error_output(32)


def is_label(argument):
    """ Funkce overi zda format argumentu instrukce odpovida navesti.
    :param argument: argument instrukce.
    :return:
    """
    if not argument.text:  # Kontrol zda obsahuje hodnotu.
        error_output(32)
    if not match(r'^[a-zA-Z\_\$\-\&\%\*][a-zA-Z\_\$\-\&\%\*\d]*$', argument.text):
        error_output(32)
    if not argument.type == "label":
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
        arg_tmp = ArgumentsClass()
        arg_tmp.text = symbol_par.value
        arg_tmp.type = "var"
        symbol = get_variable(arg_tmp)
        if not symbol.value:
            error_output(56)
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


def print_it(text):
    print_text = str(text)
    print_text_len = len(print_text)
    result = ""
    yy = 0
    while yy < print_text_len:
        char = print_text[yy]
        yy += 1
        if char == "\\":
            if yy + 3 > print_text_len:
                error_output(32)
            escape = ""
            for pp in range(0, 3):
                escape += print_text[yy + pp]
            result += chr(int(escape))
            yy += 3
        else:
            result += char
    print(result)


def is_initialized(variable):
    global global_frame
    global tmp_stack
    global frames_stack
    global data_stack
    if variable.value[:2] == "LF":
        if not frames_stack.is_empty():
            for index in range(0, frames_stack.size()):
                if frames_stack.items[index].variables:
                    for variable_in_frame in frames_stack.items[frames_stack.size() - 1].variables:  # Hledani promenne.
                        if variable_in_frame.name == variable.value[3:]:
                            return 1
    elif variable.value[:2] == "GF":
        if global_frame:
            for variable_in_frame in global_frame:  # Hledani promenne.
                if variable_in_frame.name == variable.value[3:]:
                    return 1
    elif variable.value[:2] == "TF":
        if not tmp_stack.is_empty():
            if tmp_stack.items[tmp_stack.size() - 1].variables:
                for variable_in_frame in tmp_stack.items[tmp_stack.size() - 1].variables:  # Hledani promenne.
                    if variable_in_frame.name == variable.value[3:]:
                        return 1
    return 0


def interpret(instructions_list):
    global global_frame
    global tmp_stack
    global frames_stack
    global data_stack

    labels = []
    call_stack = StackClass()

    x = 0
    while x < len(instructions_list):
        if instructions_list[x].opcode == "LABEL":
            label_var = LabelClass()
            label_var.name = instructions_list[x].arguments[0].text
            label_var.number = x
            if not labels:
                labels.append(label_var)
            else:
                for in_label in labels:
                    if in_label.name == label_var.name:
                        error_output(52)
                labels.append(label_var)
        x += 1

    x = 0
    while x < len(instructions_list):
        opcode = instructions_list[x].opcode
        if opcode == "CREATEFRAME":
            frame = FrameClass()  # Vytvoreni prazdneho ramce.
            frame.name = "TF"  # Pojmenovani ramce.
            while not tmp_stack.is_empty():  # Ulozeni ramce na TMP zasobnik.
                tmp_stack.pop()
            tmp_stack.push(frame)

        elif opcode == "PUSHFRAME":
            if tmp_stack.is_empty():
                error_output(55)
            tmp_stack.items[0].name = "LF"
            frames_stack.push(tmp_stack.pop())

        elif opcode == "POPFRAME":
            if frames_stack.is_empty():
                error_output(55)
            frames_stack.items[0].name = "TF"
            if tmp_stack:
                tmp_stack.pop()
            tmp_stack.push(frames_stack.pop())

        elif opcode == "DEFVAR":
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

        elif opcode == "PUSHS":
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

        elif opcode == "POPS":
            if data_stack.is_empty():
                error_output(56)
            var_put_in(instructions_list[x].arguments[0], data_stack.pop())

        elif opcode == "MOVE":
            variable = instructions_list[x].arguments[0]
            symbol = VariableClass()
            symbol.type = instructions_list[x].arguments[1].type
            symbol.value = instructions_list[x].arguments[1].text
            var_put_in(variable, symbol)  # Priradi symbol promenne.

        elif opcode == "ADD":
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
                        error_output(52)
                    result += int(symbol.text)
            result_var = VariableClass()
            result_var.type = "int"
            result_var.value = str(result)
            var_put_in(instructions_list[x].arguments[0], result_var)

        elif opcode == "SUB":
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
                        error_output(52)
                    if y == 1:
                        result += int(symbol.text)
                    else:
                        result -= int(symbol.text)
            result_var = VariableClass()
            result_var.type = "int"
            result_var.value = str(result)
            var_put_in(instructions_list[x].arguments[0], result_var)

        elif opcode == "MUL":
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
                        error_output(52)
                    if y == 1:
                        result += int(symbol.text)
                    else:
                        result *= int(symbol.text)
            result_var = VariableClass()
            result_var.type = "int"
            result_var.value = str(result)
            var_put_in(instructions_list[x].arguments[0], result_var)

        elif opcode == "IDIV":
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
                        error_output(52)
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

        elif opcode == "CONCAT":
            result = ""
            for y in range(1, 3):
                if instructions_list[x].arguments[y].type == "var":
                    variable = get_variable(instructions_list[x].arguments[y])
                    if not variable.type == "string":
                        error_output(53)
                    result += variable.value
                elif instructions_list[x].arguments[y].type == "string":
                    result += instructions_list[x].arguments[y].text
                else:
                    error_output(52)
            result_var = VariableClass()
            result_var.type = "string"
            result_var.value = str(result)
            var_put_in(instructions_list[x].arguments[0], result_var)

        elif opcode == "STRLEN":
            length = 0
            if instructions_list[x].arguments[1].type == "var":
                variable = get_variable(instructions_list[x].arguments[1])
                if not variable.type == "string":
                    error_output(53)
                length = len(variable.value)
            elif instructions_list[x].arguments[1].type == "string":
                length = len(instructions_list[x].arguments[1].text)
            else:
                error_output(52)
            result_var = VariableClass()
            result_var.type = "int"
            result_var.value = str(length)
            var_put_in(instructions_list[x].arguments[0], result_var)

        elif opcode == "GETCHAR":
            string_text = None
            string_len = None
            if instructions_list[x].arguments[1].type == "var":
                variable = get_variable(instructions_list[x].arguments[1])
                if not variable.type == "string":
                    error_output(53)
                string_text = variable.vaule
                string_len = int(len(string_text))
            elif instructions_list[x].arguments[1].type == "string":
                string_text = instructions_list[x].arguments[1].text
                string_len = int(len(string_text))
            else:
                error_output(52)
            char_index = None
            if instructions_list[x].arguments[2].type == "var":
                variable = get_variable(instructions_list[x].arguments[2])
                if not variable.type == "int":
                    error_output(53)
                char_index = int(variable.value)
            elif instructions_list[x].arguments[2].type == "int":
                char_index = instructions_list[x].arguments[2].text
                char_index = int(char_index)
            else:
                error_output(52)
            if char_index < 0 or char_index >= string_len:
                error_output(58)
            result_var = VariableClass()
            result_var.type = "string"
            result_var.value = str(string_text[char_index])
            var_put_in(instructions_list[x].arguments[0], result_var)

        elif opcode == "SETCHAR":
            string_text = None  # Text k modifikaci.
            string_len = None  # Delka textu k modifikaci.
            if instructions_list[x].arguments[0].type == "var":  # Zpracovani promenne.
                variable = get_variable(instructions_list[x].arguments[0])
                if not variable.type == "string":
                    error_output(53)
                string_text = variable.value
                string_len = int(len(string_text))
            else:
                error_output(53)
            char_index = None  # Index symbolu.
            if instructions_list[x].arguments[1].type == "var":  # Zpracovani indexu v symbolu 1.
                variable = get_variable(instructions_list[x].arguments[1])
                if not variable.type == "int":
                    error_output(53)
                char_index = int(variable.value)
            elif instructions_list[x].arguments[1].type == "int":
                char_index = instructions_list[x].arguments[1].text
                char_index = int(char_index)
            else:
                error_output(52)
            if char_index < 0 or char_index >= string_len:  # Kontrola zda index znaku nepresahuje rozsah.
                error_output(58)
            text_tmp = None  # Znak k nahrazeni.
            if instructions_list[x].arguments[2].type == "var":  # Zpracovani nahrazujiciho znaku.
                variable = get_variable(instructions_list[x].arguments[2])
                if not variable.type == "string":
                    error_output(53)
                if not variable.value:
                    error_output(58)
                text_tmp = variable.value[0]  # Prvni znak.
            elif instructions_list[x].arguments[2].type == "string":
                if not instructions_list[x].arguments[2].text:
                    error_output(58)
                text_tmp = instructions_list[x].arguments[2].text[0]  # Prvni znak.
            else:
                error_output(52)
            string_text = list(string_text)
            string_text[int(char_index)] = str(text_tmp)
            result_text = str(string_text)
            result_var = instructions_list[x].arguments[0]
            result_var.type = "string"
            result_var.value = str(result_text)
            var_put_in(instructions_list[x].arguments[0], result_var)

        elif opcode == "DPRINT":

            if instructions_list[x].arguments[0].type == "var":
                variable = get_variable(instructions_list[x].arguments[0])
                stderr.write(variable.value)
            else:
                stderr.write(instructions_list[x].arguments[0].text)

        elif opcode == "BREAK":
            stderr.write("Číslo instrukce: " + str(x + 1) + "\n")

        elif opcode == "LT" or opcode == "GT" or opcode == "EQ":
            symbol_1 = VariableClass()
            symbol_2 = VariableClass()
            if instructions_list[x].arguments[1].type == "var":
                symbol_1 = get_variable(instructions_list[x].arguments[1])
            else:
                symbol_1.value = instructions_list[x].arguments[1].text
                symbol_1.type = instructions_list[x].arguments[1].type
            if instructions_list[x].arguments[2].type == "var":
                symbol_2 = get_variable(instructions_list[x].arguments[2])
            else:
                symbol_2.value = instructions_list[x].arguments[2].text
                symbol_2.type = instructions_list[x].arguments[2].type
            result = None
            if symbol_1.type == "string" and symbol_2.type == "string":
                one = 0
                two = 0
                for i in symbol_1.value:
                    one += ord(i)
                for i in symbol_2.value:
                    two += ord(i)
                if opcode == "LT":
                    if one < two:
                        result = "true"
                    else:
                        result = "false"
                if opcode == "EQ":
                    if one == two:
                        result = "true"
                    else:
                        result = "false"
                if opcode == "GT":
                    if one > two:
                        result = "true"
                    else:
                        result = "false"
            elif symbol_1.type == "int" and symbol_2.type == "int":
                result_tmp = int(symbol_1.value) - int(symbol_2.value)
                if opcode == "LT":
                    if result_tmp < 0:
                        result = "true"
                    else:
                        result = "false"
                if opcode == "EQ":
                    if result_tmp == 0:
                        result = "true"
                    else:
                        result = "false"
                if opcode == "GT":
                    if result_tmp > 0:
                        result = "true"
                    else:
                        result = "false"
            elif symbol_1.type == "bool" and symbol_2.type == "bool":
                if symbol_1.value == symbol_2.value:
                    if opcode == "EQ":
                        result = "true"
                    else:
                        result = "false"
                elif symbol_1.value == "false":
                    if opcode == "LT":
                        result = "true"
                    else:
                        result = "false"
                elif opcode == "GT":
                    result = "true"
                else:
                    result = "false"
            else:
                error_output(53)
            result_var = VariableClass
            result_var.type = "bool"
            result_var.value = result
            var_put_in(instructions_list[x].arguments[0], result_var)

        elif opcode == "AND":
            symbol_1 = VariableClass()
            symbol_2 = VariableClass()
            if instructions_list[x].arguments[1].type == "var":
                symbol_1 = get_variable(instructions_list[x].arguments[1])
            else:
                symbol_1.type = instructions_list[x].arguments[1].type
                symbol_1.value = instructions_list[x].arguments[1].text
                if not symbol_1.type == "bool":
                    error_output(52)
            if instructions_list[x].arguments[2].type == "var":
                symbol_2 = get_variable(instructions_list[x].arguments[2])
            else:
                symbol_2.type = instructions_list[x].arguments[2].type
                symbol_2.value = instructions_list[x].arguments[2].text
                if not symbol_2.type == "bool":
                    error_output(52)
            if not symbol_1.type == "bool":
                error_output(53)
            if not symbol_2.type == "bool":
                error_output(53)
            one = symbol_1.value
            two = symbol_2.value
            if one == "false" and two == "false":
                result = "true"
            elif one == "true" and two == "true":
                result = "true"
            else:
                result = "false"
            variable = VariableClass()
            variable.type = "bool"
            variable.value = result
            var_put_in(instructions_list[x].arguments[0], variable)

        elif opcode == "OR":
            symbol_1 = VariableClass()
            symbol_2 = VariableClass()
            if instructions_list[x].arguments[1].type == "var":
                symbol_1 = get_variable(instructions_list[x].arguments[1])
            else:
                symbol_1.type = instructions_list[x].arguments[1].type
                symbol_1.value = instructions_list[x].arguments[1].text
                if not symbol_1.type == "bool":
                    error_output(52)
            if instructions_list[x].arguments[2].type == "var":
                symbol_2 = get_variable(instructions_list[x].arguments[2])
            else:
                symbol_2.type = instructions_list[x].arguments[2].type
                symbol_2.value = instructions_list[x].arguments[2].text
                if not symbol_2.type == "bool":
                    error_output(52)
            if not symbol_1.type == "bool":
                error_output(53)
            if not symbol_2.type == "bool":
                error_output(53)
            one = symbol_1.value
            two = symbol_2.value
            result = "false"
            if one == "true":
                result = "true"
            if two == "true":
                result = "true"
            variable = VariableClass()
            variable.type = "bool"
            variable.value = result
            var_put_in(instructions_list[x].arguments[0], variable)

        elif opcode == "NOT":
            if instructions_list[x].arguments[1].type == "var":
                symbol = get_variable(instructions_list[x].arguments[1])
            else:
                symbol = VariableClass()
                symbol.type = instructions_list[x].arguments[1].type
                if not symbol.type == "bool":
                    error_output(52)
                symbol.value = instructions_list[x].arguments[1].text
            if not symbol.type == "bool":
                error_output(53)
            if symbol.value == "true":
                result = "false"
            else:
                result = "true"
            variable = VariableClass()
            variable.type = "bool"
            variable.value = result
            var_put_in(instructions_list[x].arguments[0], variable)

        elif opcode == "INT2CHAR":
            symbol = VariableClass()
            if instructions_list[x].arguments[1].type == "var":
                symbol = get_variable(instructions_list[x].arguments[1])
            else:
                symbol.type = instructions_list[x].arguments[1].type
                symbol.value = instructions_list[x].arguments[1].text
                if symbol.type != "int":
                    error_output(52)
            result = ""
            if symbol.type != "int":
                error_output(53)
            try:
                result = chr(int(symbol.value))
            except Exception:
                error_output(58)
            variable = VariableClass()
            variable.type = "string"
            variable.value = result
            var_put_in(instructions_list[x].arguments[0], variable)

        elif opcode == "STRI2INT":
            symbol_1 = VariableClass()
            symbol_2 = VariableClass()
            if instructions_list[x].arguments[1].type == "var":
                symbol_1 = get_variable(instructions_list[x].arguments[1])
            else:
                symbol_1.type = instructions_list[x].arguments[1].type
                symbol_1.value = instructions_list[x].arguments[1].text
                if not symbol_1.type == "string":
                    error_output(52)
            if instructions_list[x].arguments[2].type == "var":
                symbol_2 = get_variable(instructions_list[x].arguments[2])
            else:
                symbol_2.type = instructions_list[x].arguments[2].type
                symbol_2.value = instructions_list[x].arguments[2].text
                if not symbol_2.type == "int":
                    error_output(52)
            if not symbol_1.type == "string":
                error_output(53)
            if not symbol_2.type == "int":
                error_output(53)
            if int(symbol_2.value) < 0 or int(symbol_2.value) > (len(symbol_1.value) - 1):
                error_output(58)
            result = ord(symbol_1.value[int(symbol_2.value)])
            variable = VariableClass()
            variable.type = "int"
            variable.value = str(result)
            var_put_in(instructions_list[x].arguments[0], variable)

        elif opcode == "WRITE":
            if instructions_list[x].arguments[0].type == "var":
                variable = get_variable(instructions_list[x].arguments[0])
                if not variable.value:
                    error_output(56)
                print_it(variable.value)
            else:
                print_it(instructions_list[x].arguments[0].text)

        elif opcode == "TYPE":
            variable = VariableClass()
            variable.type = "string"
            if instructions_list[x].arguments[1].type == "var":
                symbol = get_variable(instructions_list[x].arguments[1])
                if not symbol.type:
                    variable.value = ""
                #if not is_initialized(symbol):
                 #   variable.value = ""
            else:
                symbol.type = instructions_list[x].arguments[1].type
                symbol.value = instructions_list[x].arguments[1].text
            var_put_in(instructions_list[x].arguments[0], variable)

        elif opcode == "READ":
            type_var = instructions_list[x].arguments[1].text
            if type_var == "":
                error_output(52)
            input_text = None
            try:
                input_text = input()
            except EOFError:
                error_output(52)
            result = None
            if type_var == "string":
                try:
                    result = str(input_text)
                except ValueError:
                    error_output(52)
            if type_var == "int":
                try:
                    result = int(input_text)
                except ValueError:
                    error_output(52)
            if type_var == "bool":
                if match(r'^\s*true\s*$', input_text, IGNORECASE):
                    result = "true"
                else:
                    result = "false"
            variable = VariableClass()
            variable.type = type_var
            variable.value = str(result)
            var_put_in(instructions_list[x].arguments[0], variable)

        elif opcode == "JUMP":
            label_name = instructions_list[x].arguments[0].text
            if not labels:
                error_output(52)
            else:
                tmp_err = False
                for in_label in labels:
                    if in_label.name == label_name:
                        tmp_err = True
                        x = in_label.number
                        break
                if not tmp_err:
                    error_output(52)

        elif opcode == "JUMPIFEQ" or opcode == "JUMPIFNEQ":
            label_name = instructions_list[x].arguments[0].text
            symbol_1 = VariableClass()
            symbol_2 = VariableClass()
            if instructions_list[x].arguments[1].type == "var":
                symbol_1 = get_variable(instructions_list[x].arguments[1])
            else:
                symbol_1.type = instructions_list[x].arguments[1].type
                symbol_1.value = instructions_list[x].arguments[1].text
            if instructions_list[x].arguments[2].type == "var":
                symbol_2 = get_variable(instructions_list[x].arguments[2])
            else:
                symbol_2.type = instructions_list[x].arguments[2].type
                symbol_2.value = instructions_list[x].arguments[2].text
            if not symbol_2.type == symbol_1.type:
                error_output(53)
            if not labels:
                error_output(52)
            else:
                tmp_err = False
                for label_test in labels:
                    if label_test.name == label_name:
                        tmp_err = True
                        break
                if not tmp_err:
                    error_output(52)
                for in_label in labels:
                    if in_label.name == label_name:
                        if opcode == "JUMPIFEQ":
                            if symbol_1.value == symbol_2.value:
                                x = in_label.number
                        else:
                            if not symbol_1.value == symbol_2.value:
                                x = in_label.number
                        break

        elif opcode == "CALL":
            label_name = instructions_list[x].arguments[0].text
            if not labels:
                error_output(52)
            else:
                tmp_err = False
                for in_label in labels:
                    if in_label.name == label_name:
                        tmp_err = True
                        call_stack.push(x + 1)
                        x = in_label.number
                        break
                if not tmp_err:
                    error_output(52)

        elif opcode == "RETURN":
            if call_stack.is_empty():
                error_output(56)
            x = int(call_stack.pop() - 1)

        x += 1
    debug_var = 1


# Zpracovani parametru.
params(flags)
# Zpracovani XML programu.
instructions = xml_process(flags)
# Lexikalni analyza.
lexical_analysis(instructions)
# Interpretace instrukci.
interpret(instructions)
# print("Moskva")
exit(0)
