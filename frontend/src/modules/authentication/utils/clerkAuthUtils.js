function resolveClerkFunction(clerkFunctionRef) {
  const clerkFunction = clerkFunctionRef?.value ?? clerkFunctionRef;
  return typeof clerkFunction === 'function' ? clerkFunction : null;
}

export async function getClerkToken(getTokenRef, options = undefined) {
  const getToken = resolveClerkFunction(getTokenRef);
  return getToken ? getToken(options) : null;
}

export async function signOutClerk(signOutRef, options = undefined) {
  const signOut = resolveClerkFunction(signOutRef);
  if (!signOut) return;
  await signOut(options);
}
