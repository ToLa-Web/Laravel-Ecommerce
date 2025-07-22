
export const arraysAreEqual = (arr1: any[], arr2: any[]) =>  {
    if (arr1.length !== arr2.length) return false // check if array are the same 

    return arr1.every((value, index) => value === arr2[index]);
}