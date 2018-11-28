statement: or useStatement classStatement interfaceStatement traitStatement

useStatement: and useKeyword useBody semicolon
useKeyword: get USE
useBody: or useFunction useClass
useFunction: and useFunctionKeyword useMaps
useFunctionKeyword: get FUNCTION
useClass: or useMaps
useMaps: or useMapList useMapGroup
useMapList: and useMap anyNamespaceMapLinks
useMap: and useName maybeAlias
useName: and anyNamespaceNameLinks identifier
anyNamespaceNameLinks: any useNameLink 0+
useNameLink: and identifier useNameSeparator
maybeAlias: any alias 0 - 1
alias: and aliasKeyword identifier
anyNamespaceMapLinks: any useMapLink 0+
useMapLink: and comma useMap
comma: get COMMA
useMapGroup: and someNamespaceNameLinks leftBrace useMapList rightBrace
someNamespaceNameLinks: any useNameLink 1+
aliasKeyword: get NAMESPACE_AS
leftBrace: get BRACE_LEFT
useNameSeparator: get NAMESPACE_SEPARATOR
identifier: get IDENTIFIER
rightBrace: get BRACE_RIGHT
semicolon: get SEMICOLON

classStatement: and classTypeOptional classKeyword name extendsOptional implementsOptional
classTypeOptional: any classType 0 - 1
classType: or abstractKeyword finalKeyword
abstractKeyword: get ABSTRACT
finalKeyword: get FINAL
classKeyword: get CLASS
extendsOptional: any extends 0 - 1
extends: and extendsKeyword namePath
extendsKeyword: get EXTENDS
namePath: and name nameTailOptional
name: get IDENTIFIER
nameTailOptional: any nameTail 0+
nameTail: and useNameSeparator name
implementsOptional: any implements 0 - 1
implements: and implementsKeyword names
implementsKeyword: get IMPLEMENTS
names: and name nameLinks
nameLinks: any nameLink 0+
nameLink: and comma namePath

interfaceStatement: and interfaceKeyword name extendsListOptional
interfaceKeyword: get INTERFACE
extendsListOptional: any extendsList 0 - 1
extendsList: and extendsKeyword names

traitStatement: and traitKeyword name
traitKeyword: get TRAIT
